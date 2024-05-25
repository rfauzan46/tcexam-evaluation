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
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $selected_module_id = $_POST['subject_module_id'];
        $module_name = '';
        foreach ($modules as $module) {
            if ($module['module_id'] == $selected_module_id) {
                $module_name = $module['module_name'];
                break;
            }
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Retrieve the selected subject ID from the form submission
        $selected_subject_id = $_POST['subject'];
        
        // Fetch the corresponding subject name from your data source (e.g., database)
        $subject_name = ''; // Initialize the variable to store the subject name
        foreach ($subjects as $subject) {
            if ($subject['subject_id'] == $selected_subject_id) {
                $subject_name = $subject['subject_name'];
                break;
            }
        }
    }
    $subject = $_POST['subject'];
    $difficulty = $_POST['difficulty'];
    $language = $_POST['language'];

    echo "<p>Answer Type: " . htmlspecialchars($answer_type, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Text: " . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Module: " . htmlspecialchars($module_name, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Subject: " . htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Difficulty: " . htmlspecialchars($difficulty, ENT_QUOTES, 'UTF-8') . "</p>";
    echo "<p>Language: " . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . "</p>";

    // if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // File is uploaded, handle file upload
        $file = $_FILES['file'];

        // Upload file asynchronously
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'quizai_upload_file.php');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => new CURLFile($file['tmp_name'], $file['type'], $file['name'])]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (isset($data['file_path'])) {
            // File uploaded successfully, now send the ask_rag request
            $queryData = [
                'query' => $text,
                'file_path' => $data['file_path'],
                'answer_type' => $answer_type
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'quizai_ask_rag.php');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);

            echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
            if (isset($data['response'])) {
                echo '<pre>' . json_encode($data['response'], JSON_PRETTY_PRINT) . '</pre>';
            } else {
                echo '<p>Error: ' . ($data['error'] ?? 'Unknown error') . '</p>';
            }
            echo '</div></div></div>';
        } else {
            echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
            echo '<p>Error: ' . ($data['error'] ?? 'File upload failed') . '</p>';
            echo '</div></div></div>';
        }
    // } 
    // else {
    //     // No file uploaded, directly send the ask_rag request
    //     $queryData = [
    //         'query' => $text,
    //         'answer_type' => $answer_type
    //     ];

    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, 'quizai_ask_rag.php');
    //     curl_setopt($ch, CURLOPT_POST, 1);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($queryData));
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     $response = curl_exec($ch);
    //     curl_close($ch);

    //     $data = json_decode($response, true);

    //     echo '<div class="center-card"><div class="card" style="background-color:#F6F6F6"><div class="card-body">';
    //     if (isset($data['response'])) {
    //         echo '<pre>' . json_encode($data['response'], JSON_PRETTY_PRINT) . '</pre>';
    //     } else {
    //         echo '<p>Error: ' . ($data['error'] ?? 'Unknown error') . '</p>';
    //     }
    //     echo '</div></div></div>';
    // }
}


require_once('../code/tce_page_footer.php');
?>
