<?php
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

// Fetch modules from the database
$sql = F_select_modules_sql();
$r = F_db_query($sql, $db);
$modules = [];
while ($m = F_db_fetch_array($r)) {
    $modules[] = $m;
}

// Fetch subjects from the database based on selected module
$sql = F_select_subjects_sql('subject_module_id=' . $subject_module_id);
$r = F_db_query($sql, $db);
$subjects = [];
while ($m = F_db_fetch_array($r)) {
    $subjects[] = $m;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer_type = $_POST['answer_type'];
    $text = $_POST['text'];
    $selected_module_id = $_POST['subject_module_id'];
    $module_name = '';
    foreach ($modules as $module) {
        if ($module['module_id'] == $selected_module_id) {
            $module_name = $module['module_name'];
            break;
        }
    }
    
    $selected_subject_id = $_POST['subject'];
    $subject_name = '';
    foreach ($subjects as $subject) {
        if ($subject['subject_id'] == $selected_subject_id) {
            $subject_name = $subject['subject_name'];
            $subject_description = $subject['subject_description'];
            break;
        }
    }

    $language = $_POST['language'];

    echo "<p>Answer Type: " . htmlspecialchars($answer_type, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Text: " . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Module: " . htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Subject: " . htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Subject: " . htmlspecialchars($subject_description, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Language: " . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . "</p>";

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // File is uploaded, handle file upload
        $file = $_FILES['file'];

        // Prepare the query data
        $queryData = [
            'question_type' => $answer_type,
            'language' => $language,
            'extra_instruction' => $text,
            'subject' => $subject_name,
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])
        ];

        // Initialize cURL session for ask_rag request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:19645/ask_rag');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $queryData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Set timeout to 300 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60); // Set connection timeout to 60 seconds

        // Execute cURL request for ask_rag
        $response = curl_exec($ch);

        // Check for errors in ask_rag request
        if (curl_errno($ch)) {
            echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
            echo '<p>Error: ' . curl_error($ch) . '</p>';
            echo '</div></div></div>';
            curl_close($ch);
            exit;
        }

        // Close cURL session for ask_rag request
        curl_close($ch);

        $data = json_decode($response, true);
//         $pattern = '/\bques=(.*?)\n\s*opt_A=(.*?)\n\s*opt_B=(.*?)\n\s*opt_C=(.*?)\n\s*opt_D=(.*?)\n\s*ans=(.*?)\n\s*exp=(.*?)(?=\n\s*ques=|\n\s*$)/s';
//         preg_match_all($pattern, $data['response'], $matches, PREG_SET_ORDER);
        
//         $questions = [];
//         foreach ($matches1 as $match) {
//     $questionText = trim($match[1]);

//     if ($questionText == "Pertanyaannya") {
//         continue;
//     }

//     $optionA = trim($match[2]);
//     $optionB = trim($match[3]);
//     $optionC = trim($match[4]);
//     $optionD = trim($match[5]);
//     $answer = trim($match[6]);
//     $explanation = trim($match[7]);

//     $questions[] = [
//         'question' => $questionText,
//         'answers' => [
//             ['isright' => $answer == 'A' ? 'true' : 'false', 'description' => $optionA, 'explanation' => $answer == 'A' ? $explanation : ''],
//             ['isright' => $answer == 'B' ? 'true' : 'false', 'description' => $optionB, 'explanation' => $answer == 'B' ? $explanation : ''],
//             ['isright' => $answer == 'C' ? 'true' : 'false', 'description' => $optionC, 'explanation' => $answer == 'C' ? $explanation : ''],
//             ['isright' => $answer == 'D' ? 'true' : 'false', 'description' => $optionD, 'explanation' => $answer == 'D' ? $explanation : '']
//         ]
//     ];
// }

// foreach ($matches2 as $match) {
//     $questionText = trim($match[1]);

//     $optionA = trim($match[2]);
//     $optionB = trim($match[3]);
//     $optionC = trim($match[4]);
//     $optionD = trim($match[5]);
//     $answer = trim($match[6]);
//     $explanation = trim($match[7]);

//     $questions[] = [
//         'question' => $questionText,
//         'answers' => [
//             ['isright' => $answer == 'A' ? 'true' : 'false', 'description' => $optionA, 'explanation' => $answer == 'A' ? $explanation : ''],
//             ['isright' => $answer == 'B' ? 'true' : 'false', 'description' => $optionB, 'explanation' => $answer == 'B' ? $explanation : ''],
//             ['isright' => $answer == 'C' ? 'true' : 'false', 'description' => $optionC, 'explanation' => $answer == 'C' ? $explanation : ''],
//             ['isright' => $answer == 'D' ? 'true' : 'false', 'description' => $optionD, 'explanation' => $answer == 'D' ? $explanation : '']
//         ]
//     ];
// }
        
//         $dummyData = [
//             'module_name' => $module_name,
//             'subject_name' => $subject_name,
//             'subject_description' => $subject_description,
//             'questions' => $questions,
//         ];

        echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
        echo '<pre>' . json_encode($data['response'], JSON_PRETTY_PRINT) . '</pre>';
        echo '</br';
        // // echo '<pre>' . json_encode($dummyData, JSON_PRETTY_PRINT) . '</pre>';

        // echo '</div></div></div>';

        // echo '<style>
        // .container {
        //     display: flex;
        //     flex-wrap: wrap;
        //     justify-content: space-between;
        // }
        // .card {
        //     background-color: #F6F6F6;
        //     padding: 20px;
        //     border: 1px solid #000000;
        //     box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        //     width: 30%;
        //     margin-bottom: 20px;
        //     box-sizing: border-box;
        // }
        // </style>';

        // echo '<form method="POST" action="process.php">';
        // echo '<div class="container">';
        // foreach ($dummyData['questions'] as $qIndex => $question) {
        //     echo '<div class="card">';
        //     echo '<label for="question' . $qIndex . '"><strong>' . htmlspecialchars($question['question'], ENT_QUOTES, 'UTF-8') . '</strong></label>';
        //     echo '<div style="padding-left: 20px;">';
        //     foreach ($question['answers'] as $aIndex => $answer) {
        //         $answerText = htmlspecialchars($answer['description'], ENT_QUOTES, 'UTF-8');
        //         $isCorrect = htmlspecialchars($answer['isright'], ENT_QUOTES, 'UTF-8');
        //         $label = chr(97 + $aIndex); // 'a' is ASCII 97
        //         if ($isCorrect == 'true') {
        //             echo '<p><b>' . $label . '. ' . $answerText . '</b> (True)</p>';
        //         } else {
        //             echo '<p>' . $label . '. ' . $answerText . '</p>';
        //         }
        //     }
        //     // Add a difficulty selector for each question
        //     echo '<label for="difficulty' . $qIndex . '">Difficulty: </label>';
        //     echo '<select id="difficulty' . $qIndex . '" name="difficulty[' . $qIndex . ']">';
        //     for ($i = 1; $i <= 10; $i++) {
        //         echo '<option value="' . $i . '">' . $i . '</option>';
        //     }
        //     echo '</select>';
        //     echo '<input type="checkbox" id="question' . $qIndex . '" name="selected_questions[]" value="' . $qIndex . '" style="margin-left: 10px;">';
        //     echo '<label for="question' . $qIndex . '" style="margin-left: 5px;">Import this question</label>';
            
        //     // Include hidden inputs for each question and answer details
        //     echo '<input type="hidden" name="questions[' . $qIndex . '][question]" value="' . htmlspecialchars($question['question'], ENT_QUOTES, 'UTF-8') . '">';
        //     foreach ($question['answers'] as $aIndex => $answer) {
        //         echo '<input type="hidden" name="questions[' . $qIndex . '][answers][' . $aIndex . '][description]" value="' . htmlspecialchars($answer['description'], ENT_QUOTES, 'UTF-8') . '">';
        //         echo '<input type="hidden" name="questions[' . $qIndex . '][answers][' . $aIndex . '][isright]" value="' . htmlspecialchars($answer['isright'], ENT_QUOTES, 'UTF-8') . '">';
        //         echo '<input type="hidden" name="questions[' . $qIndex . '][answers][' . $aIndex . '][explanation]" value="' . htmlspecialchars($answer['explanation'], ENT_QUOTES, 'UTF-8') . '">';
        //     }
        //     echo '</div>';
        //     echo '</div>';
        // }
        // echo '</div>'; // Close container
        // echo '<input type="hidden" name="answer_type" value="' . htmlspecialchars($answer_type, ENT_QUOTES, 'UTF-8') . '">';
        // echo '<input type="hidden" name="text" value="' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '">';
        // echo '<input type="hidden" name="module" value="' . htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8') . '">';
        // echo '<input type="hidden" name="subject" value="' . htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8') . '">';
        // echo '<input type="hidden" name="subject_desc" value="' . htmlspecialchars($subject_description, ENT_QUOTES, 'UTF-8') . '">';
        // echo '<input type="submit" value="Submit">';
        // echo '</form>';

    } else {
        echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
        echo '<p>Error: No file uploaded or file upload error occurred.</p>';
        echo '</div></div></div>';
    }
}

require_once('../code/tce_page_footer.php');
?>
