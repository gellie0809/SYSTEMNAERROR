<?php
session_start();

// Set session for testing
$_SESSION["users"] = 'eng_admin@lspu.edu.ph';

echo "<h1>Testing Stats API</h1>";
echo "<p>Fetching data from stats_anonymous_engineering.php...</p>";

$url = 'http://localhost/SYSTEMNAERROR-3/FINALSYSTEMNAERROR/stats_anonymous_engineering.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
$response = curl_exec($ch);
curl_close($ch);

echo "<h2>Response:</h2>";
echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

echo "<h2>Decoded JSON:</h2>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";

// Also test direct database query
require_once 'db_config.php';

echo "<h2>Direct Database Query:</h2>";
$query = "SELECT COUNT(*) as total FROM anonymous_board_passers WHERE (is_deleted IS NULL OR is_deleted = 0)";
$result = mysqli_query($conn, $query);
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "Total records: " . $row['total'];
} else {
    echo "Error: " . mysqli_error($conn);
}

echo "<br><br>";
echo "<h2>Sample Data:</h2>";
$query = "SELECT * FROM anonymous_board_passers WHERE (is_deleted IS NULL OR is_deleted = 0) LIMIT 5";
$result = mysqli_query($conn, $query);
if ($result) {
    echo "<pre>";
    while ($row = mysqli_fetch_assoc($result)) {
        print_r($row);
    }
    echo "</pre>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>
