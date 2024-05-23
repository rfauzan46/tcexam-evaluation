<?php
// Include the class file containing the importFromAPI function
// Include the configuration file
require_once('../config/tce_config.php');

// Define the required page level (assuming K_AUTH_ADMIN_IMPORT is a constant)
$pagelevel = K_AUTH_ADMIN_IMPORT;

// Include the authorization file
require_once('../../shared/code/tce_authorization.php');

// Set the page title
$thispage_title = 'Question Generation Result';

// Include necessary files
require_once('../code/tce_page_header.php');
require_once('../../shared/code/tce_functions_form.php');
require_once('../../shared/code/tce_functions_tcecode.php');
require_once('../../shared/code/tce_functions_auth_sql.php');

require_once 'tce_class_import_xml.php';

// // Fetch modules from the database
// $sql = F_select_modules_sql();
// $r = F_db_query($sql, $db);
// $modules = [];
// while ($m = F_db_fetch_array($r)) {
//     $modules[] = $m;
// }

// // Fetch subjects from the database based on selected module
// $sql = F_select_subjects_sql('subject_module_id=' . $subject_module_id);
// $r = F_db_query($sql, $db);
// $subjects = [];
// while ($m = F_db_fetch_array($r)) {
//     $subjects[] = $m;
// }

// Mock data for modules
$modules = [
    ['module_id' => 1, 'module_name' => 'Bahasa Inggris'],
    // Add more modules as needed
];

// Mock data for subjects
$subjects = [
    ['subject_id' => 1, 'subject_name' => 'Grammar 1', 'subject_module_id' => 1],
    // Add more subjects as needed
];


$dummyData = [
    'module_name' => 'Apa yak',
    'subject_name' => 'gatau',
    'subject_description' => 'This is the description of the exam',
    'question' => 'Coba tebak?',
    'answers' => [
        ['isright' => 'false', 'description' => 'salah correct answer', 'explanation' => 'This is a description'],
        ['isright' => 'true', 'description' => 'this is a bener answer', 'explanation' => ''],
        ['isright' => 'false', 'description' => 'this is a kurang tepat answer', 'explanation' => ''],
        ['isright' => 'true', 'description' => 'korek answer', 'explanation' => 'This is also a correct']
    ],
    'difficulty' => '1'
];

// Create a new DOMDocument instance
$doc = new DOMDocument('1.0', 'UTF-8');

// Create the root element
$tcexamquestions = $doc->createElement('tcexamquestions');
$tcexamquestions->setAttribute('version', '13.1.1');
$doc->appendChild($tcexamquestions);

// Create the header element
$header = $doc->createElement('header');
$header->setAttribute('lang', 'en');
$header->setAttribute('date', date('Y-m-d H:i:s'));
$tcexamquestions->appendChild($header);

// Create the body element
$body = $doc->createElement('body');
$tcexamquestions->appendChild($body);

// Create the module element
$module = $doc->createElement('module');
$body->appendChild($module);

// Set module name and enabled status
$name = $doc->createElement('name', $dummyData['module_name']);
$module->appendChild($name);
$enabled = $doc->createElement('enabled', 'true');
$module->appendChild($enabled);

// Create the subject element
$subject = $doc->createElement('subject');
$module->appendChild($subject);

// Set subject name, description, and enabled status
$subjectName = $doc->createElement('name', $dummyData['subject_name']);
$subject->appendChild($subjectName);
$subjectDescription = $doc->createElement('description', $dummyData['subject_description']);
$subject->appendChild($subjectDescription);
$subjectEnabled = $doc->createElement('enabled', 'true');
$subject->appendChild($subjectEnabled);

// Create the question element
$question = $doc->createElement('question');
$subject->appendChild($question);

// Set question details
$questionEnabled = $doc->createElement('enabled', 'true');
$question->appendChild($questionEnabled);
$questionType = $doc->createElement('type', 'multiple');
$question->appendChild($questionType);
$questionDifficulty = $doc->createElement('difficulty', $dummyData['difficulty']);
$question->appendChild($questionDifficulty);
$questionDescription = $doc->createElement('description', $dummyData['question']);
$question->appendChild($questionDescription);

// Create answer elements
foreach ($dummyData['answers'] as $answerData) {
    $answer = $doc->createElement('answer');
    foreach ($answerData as $key => $value) {
        $element = $doc->createElement($key, $value);
        $answer->appendChild($element);
    }
    $question->appendChild($answer);
}

// Output the XML string
$xmlString = $doc->saveXML();
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true;
$doc->loadXML($xmlString);

// Get the formatted XML string
$formattedXml = $doc->saveXML();

// Display the formatted XML content
echo '<pre>' . htmlspecialchars($formattedXml) . '</pre>';

require_once('../code/tce_class_import_xml.php');

// Create an instance of XMLQuestionImporter and pass the XML file path
$xmlImporter = new XMLQuestionImporter($xmlString);

if ($xmlImporter) {
    F_print_error('MESSAGE', $l['m_importing_complete']);
}

?>
