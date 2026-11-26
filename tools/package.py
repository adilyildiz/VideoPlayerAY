"""Build the installable Moodle ZIP without unused legacy libraries or QA data."""
from pathlib import Path
import hashlib
import zipfile

workspace = Path(__file__).resolve().parents[1]
source = workspace / 'videoplayeray'
output = workspace / 'dist' / 'videoplayeray-2.1.0.zip'
output.parent.mkdir(exist_ok=True)
(source / 'README.md').write_bytes((workspace / 'README.md').read_bytes())
(source / 'TEST-RESULTS.md').write_bytes((workspace / 'TEST-RESULTS.md').read_bytes())
ignored = {'js', 'css', 'audio'}
with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED) as archive:
    for file in sorted(source.rglob('*')):
        relative = file.relative_to(source)
        if file.is_file() and relative.parts[0] not in ignored:
            archive.write(file, 'videoplayeray/' + relative.as_posix())
with zipfile.ZipFile(output) as archive:
    assert archive.testzip() is None
    assert all(name.startswith('videoplayeray/') for name in archive.namelist())
    assert 'videoplayeray/version.php' in archive.namelist()
    assert 'videoplayeray/questions.php' in archive.namelist()
    assert 'videoplayeray/amd/build/player.min.js' in archive.namelist()
    assert 'videoplayeray/amd/build/questions.min.js' in archive.namelist()
digest = hashlib.sha256(output.read_bytes()).hexdigest()
output.with_suffix('.zip.sha256').write_text(digest + '  ' + output.name + '\n', encoding='ascii')
print(f'{output}: {output.stat().st_size:,} bytes; SHA256 {digest}')
