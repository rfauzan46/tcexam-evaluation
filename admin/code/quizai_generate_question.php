<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File and Text Upload to API</title>
</head>
<body>
    <h2>Upload File and Input Text to API</h2>
    <form action="" method="post" enctype="multipart/form-data">
        <label for="file">Upload File:</label>
        <input type="file" name="file" id="file"><br><br>
        <label for="text">Input Text:</label>
        <input type="text" name="text" id="text"><br><br>
        <label for="answer_type">Type:</label><br>
        <input type="radio" name="answer_type" id="single_answer" value="single_answer">
        <label for="single_answer">Single Answer</label><br>
        <input type="radio" name="answer_type" id="multiple_answers" value="multiple_answers">
        <label for="multiple_answers">Multiple Answers</label><br>
        <input type="radio" name="answer_type" id="free_answer" value="free_answer">
        <label for="free_answer">Free Answer</label><br>
        <input type="radio" name="answer_type" id="ordering_answers" value="ordering_answers">
        <label for="ordering_answers">Ordering Answers</label><br><br>
        <input type="submit" name="submit" value="Submit">
    </form>

    <?php
    // Check if form is submitted
    if (isset($_POST['submit'])) {
        // API endpoint
        $api_url = 'https://api.example.com/upload';

        // Prepare data to be sent
        $data = array();

        // Add file if uploaded
        if (!empty($_FILES['file']['tmp_name'])) {
            $data['file'] = new CURLFile($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name']);
        }

        // Add text if provided
        if (!empty($_POST['text'])) {
            $data['text'] = $_POST['text'];
        }

        // Add answer type
        if (!empty($_POST['answer_type'])) {
            $data['answer_type'] = $_POST['answer_type'];
        }

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response instead of outputting it

        // Execute cURL request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        // Process API response
        if ($response) {
            echo '<p>API Response:</p>';
            echo '<pre>' . htmlspecialchars($response) . '</pre>';
        } else {
            echo '<p>No response from the API</p>';
        }
    }
    ?>
</body>
</html>