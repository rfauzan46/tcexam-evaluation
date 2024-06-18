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
    if ($answer_type === 'single') {
        $answer_type_display = 'Single Answer';
    } else if ($answer_type === 'multiple') {
        $answer_type_display = 'Multiple Answers';
    } else if ($answer_type === 'text') {
        $answer_type_display = 'Free Answer';
    } else if ($answer_type === 'ordering') {
        $answer_type_display = "Ordering Answers";
    }
    

    // $text = $_POST['text'];
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

    echo "<p>Answer Type: " . htmlspecialchars($answer_type_display, ENT_QUOTES, 'UTF-8') . "</p>";
    // echo "<p>Text: " . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Module: " . htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Subject: " . htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8') . "</p>";
    // echo "<p>Subject Description: " . htmlspecialchars($subject_description, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Language: " . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . "</p>";

    if (isset($_FILES['file'])) {
        if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // File is uploaded, handle file upload
        $file = $_FILES['file'];

        // Prepare the query data
        $queryData = [
            'question_type' => $answer_type,
            'language' => $language,
            // 'extra_instruction' => $text,
            'subject' => $subject_name,
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])
        ];

        // Initialize cURL session for ask_rag request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://34.105.76.80:20000/ask_rag');
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

        // Debug: Print the raw response
        echo '<pre>';
        echo htmlspecialchars($response);
        echo '</pre>';

        // Find the JSON part in the raw response
        $startPos = strpos($response, 'AI:');
        $jsonPartStart = $startPos + strlen('AI: Generated Questions=');
        $jsonPartEnd = strpos($response, '```', $jsonPartStart);

        
        // If the end of the JSON part is not found, use the end of the string
        if ($jsonPartEnd === false) {
            $jsonPartEnd = strlen($response);
        }

        $jsonPart = substr($response, $jsonPartStart, $jsonPartEnd - $jsonPartStart);

        // // Debug: Print the JSON part
        echo '<pre>';
        echo 'Extracted JSON part:<br>';
        echo htmlspecialchars($jsonPart);
        echo '</pre>';
        

        // Decode the JSON part
        $data = json_decode($jsonPart, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            die('JSON decode error: ' . json_last_error_msg());
        }

        $questions = [];

        foreach ($data as $questionData) {
            $question = [
                'question' => $questionData['question'],
                'answers' => []
            ];
        
            if (isset($questionData['option']) && is_array($questionData['option'])) {
                foreach ($questionData['option'] as $option) {
                    $question['answers'][] = [
                        'isright' => $option == $questionData['answer'] ? 'true' : 'false',
                        'description' => $option,
                        'explanation' => $option == $questionData['answer'] ? $questionData['explanation'] : ''
                    ];
                }
            } else {
                // Handle essay question
                $question['answers'][] = [
                    'isright' => 'true',  // Essay questions typically have one correct answer
                    'description' => $questionData['answer'],
                    'explanation' => $questionData['explanation']
                ];
            }
        
            $questions[] = $question;
        }

        $dummyData = [
            'module_name' => $module_name,
            'subject_name' => $subject_name,
            'subject_description' => $subject_description,
            'questions' => $questions
        ];


        // echo json_encode($dummyData, JSON_PRETTY_PRINT);

        // echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
        // echo '<pre>' . json_encode($data, JSON_PRETTY_PRINT) . '</pre>';
        // echo '</br';
        // // echo '<pre>' . json_encode($dummyData, JSON_PRETTY_PRINT) . '</pre>';

        echo '</div></div></div>';

        echo '<style>
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .card {
            background-color: #F6F6F6;
            padding: 20px;
            border: 1px solid #000000;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 30%;
            margin-bottom: 20px;
            box-sizing: border-box;
        }
        </style>';

        echo '<form method="POST" action="process.php">';
        echo '<div class="container">';
        foreach ($dummyData['questions'] as $qIndex => $question) {
            echo '<div class="card">';
            echo '<label for="question' . $qIndex . '"><strong>' . htmlspecialchars($question['question'], ENT_QUOTES, 'UTF-8') . '</strong></label>';
            echo '<div style="padding-left: 20px;">';
            foreach ($question['answers'] as $aIndex => $answer) {
                $answerText = htmlspecialchars($answer['description'], ENT_QUOTES, 'UTF-8');
                $isCorrect = htmlspecialchars($answer['isright'], ENT_QUOTES, 'UTF-8');

                // Check if there is more than one answer
                if (count($question['answers']) > 1) {
                    $label = chr(97 + $aIndex); // 'a' is ASCII 97
                    $labelText = $label . '. ';
                } else {
                    // If there is only one answer, don't display the label
                    $labelText = '';
                }

                if ($isCorrect == 'true') {
                    echo '<p><mark>' . $labelText . $answerText . '</mark></p>';
                } else {
                    echo '<p>' . $labelText . $answerText . '</p>';
                }
            }
            // Add a difficulty selector for each question
            echo '<label for="difficulty' . $qIndex . '">Difficulty: </label>';
            echo '<select id="difficulty' . $qIndex . '" name="difficulty[' . $qIndex . ']">';
            for ($i = 1; $i <= 10; $i++) {
                echo '<option value="' . $i . '">' . $i . '</option>';
            }
            echo '</select>';
            echo '<input type="checkbox" id="question' . $qIndex . '" name="selected_questions[]" value="' . $qIndex . '" style="margin-left: 10px;">';
            echo '<label for="question' . $qIndex . '" style="margin-left: 5px;">Import this question</label>';
            
            // Include hidden inputs for each question and answer details
            echo '<input type="hidden" name="questions[' . $qIndex . '][question]" value="' . htmlspecialchars($question['question'], ENT_QUOTES, 'UTF-8') . '">';
            foreach ($question['answers'] as $aIndex => $answer) {
                echo '<input type="hidden" name="questions[' . $qIndex . '][answers][' . $aIndex . '][description]" value="' . htmlspecialchars($answer['description'], ENT_QUOTES, 'UTF-8') . '">';
                echo '<input type="hidden" name="questions[' . $qIndex . '][answers][' . $aIndex . '][isright]" value="' . htmlspecialchars($answer['isright'], ENT_QUOTES, 'UTF-8') . '">';
                echo '<input type="hidden" name="questions[' . $qIndex . '][answers][' . $aIndex . '][explanation]" value="' . htmlspecialchars($answer['explanation'], ENT_QUOTES, 'UTF-8') . '">';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>'; // Close container
        echo '<input type="hidden" name="answer_type" value="' . htmlspecialchars($answer_type, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="text" value="' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="module" value="' . htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="subject" value="' . htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="subject_desc" value="' . htmlspecialchars($subject_description, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="submit" value="Submit">';
        echo '</form>';
        } else {
            switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    echo "<p>Error: The uploaded file exceeds the upload_max_filesize directive in php.ini.</p>";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    echo "<p>Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.</p>";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    echo "<p>Error: The uploaded file was only partially uploaded.</p>";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    echo "<p>Error: No file was uploaded.</p>";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    echo "<p>Error: Missing a temporary folder.</p>";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    echo "<p>Error: Failed to write file to disk.</p>";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    echo "<p>Error: A PHP extension stopped the file upload.</p>";
                    break;
                default:
                    echo "<p>Error: Unknown upload error.</p>";
                    break;
            }
        }
    } else {
        echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
        echo '<p>No file was uploaded or there was an error uploading the file.</p>';
        echo '</div></div></div>';
    }
}

require_once('../code/tce_page_footer.php');
?>
