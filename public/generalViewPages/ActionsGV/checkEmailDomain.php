<?php
header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');
if ($email === '') {
    echo json_encode(['valid' => false, 'message' => 'Email address is required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['valid' => false, 'message' => 'Please enter a valid email address format.']);
    exit;
}

$parts = explode('@', $email);
if (count($parts) !== 2) {
    echo json_encode(['valid' => false, 'message' => 'Please enter a valid email address format.']);
    exit;
}

$user = strtolower($parts[0]);
$domain = strtolower($parts[1]);

// 1. Known domain typos
$typoMap = [
    'gmaidla.com' => 'gmail.com',
    'gmaild.com'  => 'gmail.com',
    'gamil.com'   => 'gmail.com',
    'gmial.com'   => 'gmail.com',
    'gmaill.com'  => 'gmail.com',
    'gmai.com'    => 'gmail.com',
    'gmal.com'    => 'gmail.com',
    'gmeil.com'   => 'gmail.com',
    'gmaio.com'   => 'gmail.com',
    'gmail.co'    => 'gmail.com',
    'gmaill.co'   => 'gmail.com',
    'yaho.com'    => 'yahoo.com',
    'yahooo.com'  => 'yahoo.com',
    'yaho.co'     => 'yahoo.com',
    'ymail.co'    => 'yahoo.com',
    'outlok.com'  => 'outlook.com',
    'outloo.com'  => 'outlook.com',
    'hotmial.com' => 'hotmail.com',
    'hotmai.com'  => 'hotmail.com',
    'iclou.com'   => 'icloud.com',
    'icld.com'    => 'icloud.com',
];

if (isset($typoMap[$domain])) {
    echo json_encode([
        'valid' => false,
        'message' => 'Please enter a valid email domain provider (e.g. name@gmail.com).'
    ]);
    exit;
}

$majorDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com'];
foreach ($majorDomains as $major) {
    if ($domain !== $major && levenshtein($domain, $major) <= 2) {
        echo json_encode([
            'valid' => false,
            'message' => 'Please enter a valid email domain provider (e.g. name@gmail.com).'
        ]);
        exit;
    }
}

// 2. Temporary / disposable email providers
$disposableDomains = [
    'tempmail.com', '10minutemail.com', 'mailinator.com', 
    'guerrillamail.com', 'throwawaymail.com', 'yopmail.com',
    'trashmail.com', 'getairmail.com', 'fakemailgenerator.com'
];
if (in_array($domain, $disposableDomains, true)) {
    echo json_encode([
        'valid' => false,
        'message' => 'Please enter a valid personal or business email domain.'
    ]);
    exit;
}

// 3. DNS MX record check (checks if the domain has a mail exchange server)
if (!checkdnsrr($domain, 'MX')) {
    echo json_encode([
        'valid' => false,
        'message' => 'Please enter a valid email domain provider (e.g. name@gmail.com).'
    ]);
    exit;
}

echo json_encode(['valid' => true]);
