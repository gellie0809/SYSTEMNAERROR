<?php
// delete_course.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'No course ID provided.']);
        exit;
    }
    $id = intval($_POST['id']);
    $conn = new mysqli('localhost', 'root', '', 'project_db');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit;
    }
    $stmt = $conn->prepare('DELETE FROM courses WHERE id = ?');
    $stmt->bind_param('i', $id);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => $success]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid request.']);
