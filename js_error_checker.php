<?php
// JavaScript Error Checker and Fixer for dashboard_engineering.php
$filePath = 'dashboard_engineering.php';
$content = file_get_contents($filePath);

echo "<h2>🔧 JavaScript Error Analysis</h2>\n";

// Check for common JavaScript errors
$errors = [];

// 1. Check for unclosed functions
$openBraces = substr_count($content, '{');
$closeBraces = substr_count($content, '}');
if ($openBraces !== $closeBraces) {
    $errors[] = "❌ Mismatched braces: {$openBraces} opening vs {$closeBraces} closing";
}

// 2. Check for unclosed parentheses in functions
preg_match_all('/function\s+\w+\s*\([^)]*\)\s*\{/', $content, $functions);
echo "<p>✅ Found " . count($functions[0]) . " function declarations</p>\n";

// 3. Check for incomplete if statements
preg_match_all('/if\s*\([^)]+\)\s*\{[^}]*\}?\s*else\s*\{[^}]*\}?/', $content, $ifStatements);
echo "<p>✅ Found " . count($ifStatements[0]) . " if-else statements</p>\n";

// 4. Check for incomplete event listeners
preg_match_all('/addEventListener\s*\(\s*[\'"][^\'\"]+[\'"]\s*,\s*function\s*\([^)]*\)\s*\{/', $content, $listeners);
echo "<p>✅ Found " . count($listeners[0]) . " event listeners</p>\n";

// 5. Check for duplicate function names
preg_match_all('/function\s+(\w+)\s*\(/', $content, $functionNames);
$duplicates = array_count_values($functionNames[1]);
$duplicateFunctions = array_filter($duplicates, function($count) { return $count > 1; });

if (!empty($duplicateFunctions)) {
    echo "<h3>❌ Duplicate Functions Found:</h3>\n";
    foreach ($duplicateFunctions as $name => $count) {
        echo "<p>- Function <strong>{$name}</strong> defined {$count} times</p>\n";
        $errors[] = "Duplicate function: {$name}";
    }
}

// 6. Check for incomplete switch statements
preg_match_all('/switch\s*\([^)]+\)\s*\{[^}]*case[^}]*break[^}]*\}/', $content, $switches);
echo "<p>✅ Found " . count($switches[0]) . " complete switch statements</p>\n";

// 7. Check for missing semicolons (common patterns)
$missingSemicolons = [];
if (strpos($content, '}\n    function') !== false) {
    $missingSemicolons[] = "Missing semicolons between function definitions";
}

// Display results
if (empty($errors) && empty($missingSemicolons)) {
    echo "<h3>✅ No Critical JavaScript Syntax Errors Found!</h3>\n";
    echo "<p>The JavaScript structure appears to be valid.</p>\n";
} else {
    echo "<h3>❌ Issues Found:</h3>\n";
    foreach ($errors as $error) {
        echo "<p>🔴 {$error}</p>\n";
    }
    foreach ($missingSemicolons as $error) {
        echo "<p>🟡 {$error}</p>\n";
    }
}

// Check specific problematic patterns
echo "<h3>🔍 Detailed Analysis:</h3>\n";

// Check for incomplete template literals
$incompleteTemplates = preg_match_all('/`[^`]*\$\{[^}]*\}[^`]*`/', $content, $templates);
echo "<p>✅ Found {$incompleteTemplates} template literals</p>\n";

// Check for console.log statements (for debugging)
$consoleLogs = substr_count($content, 'console.log');
echo "<p>🔍 Found {$consoleLogs} console.log statements (for debugging)</p>\n";

// Check for error handling
$tryBlocks = substr_count($content, 'try {');
$catchBlocks = substr_count($content, 'catch');
echo "<p>✅ Found {$tryBlocks} try blocks and {$catchBlocks} catch blocks</p>\n";

// Summary
echo "<hr>\n";
echo "<h3>📊 Summary:</h3>\n";
echo "<ul>\n";
echo "<li>Total functions: " . count($functionNames[1]) . "</li>\n";
echo "<li>Event listeners: " . count($listeners[0]) . "</li>\n";
echo "<li>Brace balance: " . ($openBraces === $closeBraces ? '✅ Balanced' : '❌ Unbalanced') . "</li>\n";
echo "<li>Console logs: {$consoleLogs} (remove for production)</li>\n";
echo "</ul>\n";

if (empty($errors)) {
    echo "<p><strong>🎉 The file appears to be syntactically correct!</strong></p>\n";
    echo "<p>If you're experiencing errors, they might be:</p>\n";
    echo "<ul>\n";
    echo "<li>Runtime errors (check browser console)</li>\n";
    echo "<li>Missing DOM elements</li>\n";
    echo "<li>Network/server errors</li>\n";
    echo "<li>PHP errors preventing page load</li>\n";
    echo "</ul>\n";
}

echo "<p><a href='dashboard_engineering.php'>🔄 Test Dashboard</a></p>\n";
?>
