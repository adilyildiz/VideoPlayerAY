<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * English strings for mod_videoplayeray.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Video Analiz';
$string['modulenameplural'] = 'Video Analiz';
$string['modulename_help'] = 'Create tracked video activities with timed interactive questions.';
$string['novideoplayerays'] = 'There are no Video Analiz activities in this course.';
$string['videoplayeray'] = 'Video Analiz';
$string['videoplayerayname'] = 'Video Analiz name';
$string['videoplayerayname_help'] = 'Enter the display name of this video activity.';
$string['videoplayerayadres'] = 'Video URL';
$string['videoplayerayoverlayjs'] = 'Legacy olaylar.js URL';
$string['videoplayerayoverlayjs_help'] = 'Legacy format: var olaylar = {"2":["Question 1",[["Option 1",false],["Option 2",true]],0]}';
$string['pluginadministration'] = 'Video Analiz administration';
$string['pluginname'] = 'Video Analiz';

$string['videoplayeray:addinstance'] = 'Add a new Video Analiz activity';
$string['videoplayeray:submit'] = 'Record Video Analiz progress';
$string['videoplayeray:view'] = 'View Video Analiz';
$string['videoplayeray:viewreports'] = 'View video reports';

$string['complete'] = 'Completed';
$string['incomplete'] = 'In progress';
$string['smshttps'] = 'The SMS API requires an HTTPS address.';
$string['smssoap'] = 'SOAP SMS APIs require a provider-specific integration.';
$string['smsfailed'] = 'SMS API request failed. Check the provider configuration.';
$string['participantdetail'] = 'Participant viewing history';
$string['event'] = 'Event';
$string['result'] = 'Result';
$string['correct'] = 'Correct';
$string['incorrect'] = 'Incorrect';
$string['noevents'] = 'No playback or answer events recorded yet.';
$string['event_sorusoruldu'] = 'Question shown';
$string['event_cevapverildi'] = 'Answer submitted';
$string['event_izlemebasladi'] = 'Playback started';
$string['event_izlemedevamedildi'] = 'Playback resumed';
$string['event_izlemebekletildi'] = 'Playback paused';
$string['event_izlemekaydirildi'] = 'Seeked';
$string['event_tamekran'] = 'Fullscreen changed';
$string['event_seskes'] = 'Sound changed';
$string['event_izlemebitti'] = 'Playback ended';

$string['report'] = 'Video report';
$string['coursereport'] = 'Course video report';
$string['allcoursesreport'] = 'All courses video report';
$string['views'] = 'Completed viewings';
$string['progress'] = 'Current viewing progress';
$string['questionssolved'] = 'Questions answered correctly';
$string['questionanalytics'] = 'Question success analysis';
$string['attempts'] = 'Attempts';
$string['successrate'] = 'Success rate';

$string['question'] = 'Video question';
$string['answer'] = 'Answer';
$string['selectanswer'] = 'Select an answer first.';
$string['correctanswer'] = 'Correct. Continuing the video.';
$string['wronganswer'] = 'Incorrect. Returning to the earlier section.';
$string['continuevideo'] = 'Continue video';
$string['rewindandwatch'] = 'Return and rewatch';
$string['cannotseekquestion'] = 'Answer the required question correctly before skipping ahead.';
$string['missingquestionsmessage'] = 'You must answer {missing} more required question(s) correctly to complete this video.';
$string['trackingfailed'] = 'Progress could not be saved. Check your connection and reload before continuing.';
$string['completedmessage'] = 'Completed viewings: {count}.';
$string['incompletemessage'] = 'You watched {percent}% of the video. Continue watching the missing sections.';
$string['filenotfound'] = 'No video file is available. Edit the activity and upload a video.';
$string['notenrolled'] = 'An active course enrolment is required to record video progress.';
$string['invalidprogress'] = 'Invalid video progress data.';
$string['invalidoverlay'] = 'Enter an HTTP(S) URL of at most 255 characters, or leave this field empty.';
$string['overlaypermission'] = 'Changing executable question scripts requires the trust content capability.';
$string['resourcecontent'] = 'Video file';
$string['taskname'] = 'Video viewing SMS reminders';
$string['saved'] = 'Progress saved';
$string['saving'] = 'Saving progress…';
$string['autosave'] = 'Your progress is saved automatically.';
$string['resume'] = 'Continue where I left off';
$string['previewmode'] = 'Preview: viewing progress will not be recorded.';
$string['learningprogress'] = 'YOUR PROGRESS';
$string['viewinggoal'] = 'Watch at least {$a->percent}% of this video, {$a->count} time(s).';
$string['questionsprogress'] = 'Questions';

$string['questions'] = 'Timed questions';
$string['managequestions'] = 'Manage questions';
$string['managequestionsintro'] = 'Play the video and pause at any moment to add a question. Enter times as mm:ss (e.g. 01:30) or seconds (90).';
$string['questionssavefirst'] = 'Save the activity and upload a video first, then use the "Timed questions" tab to add questions while previewing the video.';
$string['questionscountinfo'] = '{$a} timed question(s) configured for this activity.';
$string['questionlist'] = 'Configured questions';
$string['noquestions'] = 'No questions added yet. Use the form below to add your first question.';
$string['questionnumber'] = 'Question {no}';
$string['questionsintro'] = 'Set when the question appears, provide answers, and choose the correct option.';
$string['questiontime'] = 'Show at (mm:ss or seconds)';
$string['questiontime_help'] = 'Enter when the question should appear, either as mm:ss (e.g. 01:30) or total seconds (90).';
$string['rewindtime'] = 'On wrong answer, return to';
$string['rewindtime_help'] = 'Enter the timestamp to rewind to after a wrong answer (between 00:00 and the question time).';
$string['questiontype'] = 'Question type';
$string['qtype_multichoice'] = 'Multiple choice';
$string['qtype_truefalse'] = 'True / False';
$string['optiontrue'] = 'True';
$string['optionfalse'] = 'False';
$string['answernumber'] = 'Option {$a}';
$string['correctoption'] = 'Correct option';
$string['questionrequired'] = 'Required question';
$string['questionrequired_desc'] = 'Prevent skipping past this question and require a correct answer for completion';
$string['shuffleoptions'] = 'Shuffle options';
$string['shuffleoptions_desc'] = 'Randomize answer option order each time the question is shown';
$string['feedbackcorrect'] = 'Correct answer feedback (optional)';
$string['feedbackwrong'] = 'Wrong answer feedback (optional)';
$string['addquestion'] = 'Add question';
$string['editquestion'] = 'Edit question';
$string['questionadded'] = 'Question added.';
$string['questionupdated'] = 'Question updated.';
$string['questiondeleted'] = 'Question deleted.';
$string['confirmdeletequestion'] = 'Are you sure you want to delete this question?';
$string['currenttime'] = 'Current time';
$string['usecurrenttime'] = 'Use as question time';
$string['userewindtime'] = 'Use as rewind time';
$string['beyondduration'] = 'Exceeds video duration';
$string['optionscount'] = '{$a} options';
$string['legacyquestions'] = 'Advanced: legacy question script';
$string['invalidquestiontime'] = 'Enter a valid time between 00:00 and 23:59:59 (0–86399 seconds).';
$string['duplicatequestiontime'] = 'Another question is already scheduled at this timestamp.';
$string['invalidrewind'] = 'The return time must be between zero and the question time.';
$string['invalidquestion'] = 'Enter a question, at least two answers, and a non-empty correct option.';
$string['toomanyquestions'] = 'You can add up to 100 questions.';

$string['searchuser'] = 'Search participant';
$string['downloadcsv'] = 'Download CSV';
$string['noreportrows'] = 'No participants match these filters.';
$string['reportintro'] = 'See who has finished, question results, and who may need support. Progress shows the current viewing cycle.';
$string['reportsummary'] = '{$a->complete} completed / {$a->total} participant–video records';
$string['resetprogress'] = 'Delete all viewing progress and question answer logs';

$string['setting_apiadres'] = 'SMS API URL';
$string['setting_kullanici'] = 'SMS API Username';
$string['setting_sifre'] = 'SMS API Password';
$string['setting_gonderen'] = 'SMS Sender ID';
$string['setting_apituru'] = 'API Type';
$string['setting_smsat'] = 'Enable Student SMS Reminders';
$string['setting_minviews'] = 'Minimum Required Completed Viewings';
$string['setting_threshold'] = 'Minimum Watched Percentage';
$string['setting_adminsms'] = 'Enable Admin SMS Summary';
$string['setting_adminphone'] = 'Admin Phone Number';

$string['privacy:metadata:views'] = 'Video viewing progress, answered questions, and completion counts.';
$string['privacy:metadata:events'] = 'Playback and question interaction history.';
$string['privacy:metadata:userid'] = 'The participant identifier.';
$string['privacy:metadata:videoid'] = 'The course module identifier.';
$string['privacy:metadata:izlenme'] = 'The watched seconds in the current viewing.';
$string['privacy:metadata:izlenmesayisi'] = 'The number of completed viewings.';
$string['privacy:metadata:position'] = 'The last playback position in seconds.';
$string['privacy:metadata:answered'] = 'Question identifiers answered correctly in the current viewing.';
$string['privacy:metadata:eventdata'] = 'Playback events, questions, answers, timing and historical user snapshots.';
$string['privacy:metadata:sms'] = 'Optional reminders sent to the configured SMS provider.';
$string['privacy:metadata:phone'] = 'The recipient phone number.';
$string['privacy:metadata:message'] = 'A reminder containing the recipient name and missing viewing count.';
