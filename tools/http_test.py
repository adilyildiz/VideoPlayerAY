"""HTTP regression tests against a disposable local Moodle fixture."""
import http.cookiejar
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path

branch, port = sys.argv[1:]
base = 'http://127.0.0.1:' + port
fixture = json.loads((Path(__file__).resolve().parents[1] / '.qa' / f'fixture-{branch}.json').read_text())

def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

def read(opener, path, data=None):
    try:
        response = opener.open(base + path, urllib.parse.urlencode(data).encode() if data is not None else None, timeout=40)
    except urllib.error.HTTPError as error:
        response = error
    return response.read().decode('utf-8-sig')

def login(username):
    opener = client()
    html = read(opener, '/login/index.php')
    token = re.search(r'name="logintoken" value="([^"]+)"', html).group(1)
    html = read(opener, '/login/index.php', {'username': username, 'password': 'VideoPlayerQA_Only_2026', 'logintoken': token})
    assert 'Invalid login' not in html
    return opener

student = login(fixture['username'])
view = read(student, '/mod/videoplayeray/view.php?id=' + str(fixture['cmid']))
Path('.qa/http-view-' + branch + '.html').write_text(view, encoding='utf-8')
assert 'videoplayeray-progress' in view
assert 'videoplayeray-qprogress' in view
sesskey = re.search(r'"sesskey":"([^"]+)"', view).group(1)

def post(script, data):
    return json.loads(read(student, '/mod/videoplayeray/' + script + '.php', data))

# Student must not access question management page.
qpage_student = read(student, '/mod/videoplayeray/questions.php?id=' + str(fixture['cmid']))
assert 'videoplayeray-qform' not in qpage_student, 'student must not manage questions'

assert post('videoizlenmeal', {'videoid': fixture['cmid']}).get('error'), 'CSRF guard'
assert post('videoizlenmeisle', {'videoid': fixture['cmid'], 'sesskey': sesskey, 'progress': '[1, {}]'}).get('error')
assert post('videoizlenmeisle', {'videoid': fixture['cmid'], 'sesskey': sesskey,
    'progress': json.dumps([1] * 95 + [0] * 5), 'position': 85})['ok']
data = post('videoizlenmeal', {'videoid': fixture['cmid'], 'sesskey': sesskey})
assert data['position'] == 85 and len(json.loads(data['veri'])) == 100

# Completion must be blocked while required question is unanswered.
blocked = post('videocomplete', {'id': fixture['cmid'], 'sesskey': sesskey})
assert blocked[0] == 'eksik_soru' and blocked[3] == 1, f'expected eksik_soru, got {blocked}'

# Answer the required question correctly.
ans = post('videocevapisle', {'videoid': fixture['cmid'], 'sesskey': sesskey,
    'questionid': fixture['questionid'], 'tur': 'cevapverildi',
    'soru': 'QA question', 'cevap': '<script>alert(1)</script>', 'durum': 1})
assert ans['ok'] and fixture['questionid'] in ans['answered']

# Now completion succeeds!
result = post('videocomplete', {'id': fixture['cmid'], 'sesskey': sesskey})
assert result[0] == 'tam_izlenme' and result[1] == 95, f'expected tam_izlenme, got {result}'
repeat = post('videocomplete', {'id': fixture['cmid'], 'sesskey': sesskey})
assert repeat[0] == 'eksik_izlenme' and repeat[2] == result[2], 'duplicate completion guard'

report = read(student, '/mod/videoplayeray/report.php?id=' + str(fixture['cmid']))
assert 'videoplayeray-filters' not in report, 'student must not read reports'
detailpath = '/mod/videoplayeray/detail.php?' + urllib.parse.urlencode({'id': fixture['cmid'], 'userid': fixture['userid']})
assert 'videoplayeray-reportsummary' not in read(student, detailpath), 'student must not read event reports'

admin = login('admin')
qpage_admin = read(admin, '/mod/videoplayeray/questions.php?id=' + str(fixture['cmid']))
assert 'videoplayeray-qform' in qpage_admin and 'What is 2 + 2?' in qpage_admin

report = read(admin, '/mod/videoplayeray/report.php?id=' + str(fixture['cmid']))
assert 'videoplayeray-filters' in report and '[[complete]]' not in report and 'What is 2 + 2?' in report
adminsesskey = re.search(r'"sesskey":"([^"]+)"', report).group(1)
csv = read(admin, '/mod/videoplayeray/report.php?' + urllib.parse.urlencode({
    'id': fixture['cmid'], 'download': 1, 'sesskey': adminsesskey}))
assert 'Video QA' in csv and '<html' not in csv
detail = read(admin, detailpath)
assert 'videoplayeray-reportsummary' in detail and 'QA question' in detail
assert '<script>alert(1)</script>' not in detail
print('PASS: HTTP login, student view, question manager ACL, CSRF rejection, invalid input, required question completion gate, 95% threshold, resume position, duplicate completion, student report/detail denial, event recording, escaped event detail, admin report, question analytics, and CSV')
