<?php
// Read the query and mission data from files
$query = file('data.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$filename = 'misi.txt';
$missionIds = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Initialize variables
$data = json_encode([]); // Empty JSON object
echo "Masukan Ref: ";
$ref = trim(fgets(STDIN));

echo "Do you want to upgrade farm? (y/n): ";
$farm = trim(fgets(STDIN));

echo "Do you want to upgrade population? (y/n): ";
$populations = trim(fgets(STDIN));

echo "Do you want to upgrade cards? (y/n): ";
$upgradeCard = trim(fgets(STDIN));

if ($upgradeCard === 'y') {
    echo "Upgrade Economy? (y/n): ";
    $ekonomi = trim(fgets(STDIN));

    echo "Upgrade Military? (y/n): ";
    $militer = trim(fgets(STDIN));

    echo "Upgrade Science? (y/n): ";
    $science = trim(fgets(STDIN));
}

echo "Is this a new account? (y/n): ";
$permisi = trim(fgets(STDIN));

// Function to handle upgrades
function upgradeUser($upgradeType, $queries) {
    $url = 'https://api.thevertus.app/users/upgrade';
    $data = json_encode(['upgrade' => $upgradeType]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data),
        'Authorization: Bearer ' . $queries,
        'Accept: application/json, text/plain, */*',
        'User-Agent: Mozilla/5.0 (Linux; Android 9; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Mobile Safari/537.36',
        'Origin: https://thevertus.app',
        'X-Requested-With: org.telegram.messenger'
    ]);

    $response = curl_exec($ch);
    $data = json_decode($response, true);

    if ($data['success']) {
        foreach ($data['abilities'] as $ability => $details) {
            echo "Ability: " . ucfirst($ability) . "\n";
            echo "  Title: " . $details['title'] . "\n";
            echo "  Level: " . $details['level'] . "\n";
            echo "  Value: " . $details['value'] . "\n";
            echo "  Description: " . $details['description'] . "\n";
            echo "  Price to Level Up: " . $details['priceToLevelUp'] . "\n";
            echo "  Next Level: " . $details['nextLevel']['title'] . " (Level " . $details['nextLevel']['level'] . ")\n";
            echo "  Next Level Price: " . $details['nextLevel']['priceToLevelUp'] . "\n";
        }
        echo "New Balance: " . $data['newBalance'] . "\n";
    } else {
        echo "Error in response. $response\n";
    }
    curl_close($ch);
}

// Function to make a cURL request
if (!function_exists('makeCurlRequest')) {
    function makeCurlRequest($queries, $url, $method = 'GET', $data = null) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json, text/plain, */*',
            'Authorization: Bearer ' . $queries,
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Linux; Android 9; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Mobile Safari/537.36',
            'Origin: https://thevertus.app',
            'X-Requested-With: org.telegram.messenger',
            'Referer: https://thevertus.app/',
            'Accept-Language: id,id-ID;q=0.9,en-US;q=0.8,en;q=0.7'
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        curl_close($ch);
        return json_decode($response, true);
    }
}

// Function to upgrade cards
function upgradeCards($cards, $queries) {
    $url = 'https://api.thevertus.app/upgrade-cards/upgrade';

    foreach ($cards as $card) {
        $data = ['cardId' => $card['_id']];
        $response = makeCurlRequest($queries, $url, 'POST', $data);

        if (isset($response['isSuccess']) && $response['isSuccess']) {
            $earn = $response['newValuePerHour'] / 1000000000000000000;
            echo "Upgrade successful! For Card {$card['cardName']} New Earn/Hours: {$earn}\n";
            sleep(2);
        } else {
            echo "Upgrade failed: " . ($response['message'] ?? 'Unknown error') . "\n";
        }
    }
}

// Main logic for iterating over queries
while(true){
foreach ($query as $index => $queries) {
    $url = 'https://api.thevertus.app/users/get-data';
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data),
        'Authorization: Bearer ' . $queries,
        'User-Agent: Mozilla/5.0 (Linux; Android 9; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Mobile Safari/537.36',
        'Ref: ' . $ref,
        'Origin: https://thevertus.app',
        'X-Requested-With: org.telegram.messenger'
    ]);

    $response = curl_exec($ch);
    $datta = json_decode($response, true);

    if (isset($datta['isValid']) && $datta['isValid'] === true) {
        // Process user data
        $user = $datta['user'];
        $telegramId = $user['telegramId'];
        $wallet = $user['walletAddress'];
        $balance = $user['balance'];

        echo "Success login with Telegram ID: $telegramId\n";
        echo "Wallet Address: $wallet\n";
        echo "Balance: " . ($balance / 1000000000000000000) . "\n";

        // Create wallet if it's a new account and clear task
        if ($permisi === 'y') {
            $url = 'https://api.thevertus.app/users/create-wallet';

            // Create a cURL handle
            $ch = curl_init($url);

            // Prepare the headers
            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer '.$queries.'',
                'Accept: application/json, text/plain, */*',
                'User-Agent: Mozilla/5.0 (Linux; Android 9; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Mobile Safari/537.36',
                'Origin: https://thevertus.app',
                'X-Requested-With: org.telegram.messenger',
            ];

            // Set cURL options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([])); // Empty JSON body

            // Execute the request
            $response = curl_exec($ch);

            // Check for errors
            if (curl_errno($ch)) {
                echo 'Error: ' . curl_error($ch);
            } else {
                echo 'Response: ' . $response  . "\n";
            }

            // Close the cURL handle
            curl_close($ch);

        // Process missions
        foreach ($missionIds as $missionId) {
            // Buat data JSON untuk permintaan
            $data = json_encode(['missionId' => $missionId]);
        
            // Inisialisasi cURL
            $ch = curl_init('https://api.thevertus.app/missions/complete');
        
            // Set opsi cURL
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer '.$queries.''
            ]);
        
            // Eksekusi cURL
            $response = curl_exec($ch);
        
            // Cek untuk kesalahan
            if (curl_errno($ch)) {
                echo 'Curl error: ' . curl_error($ch) . "\n";
            } else {
                echo "Response for missionId $missionId: $response\n";
            }
        
            // Tutup cURL
            curl_close($ch);
        }
        

        }
        // Handle farm and population upgrades
        if ($farm === 'y') {
            upgradeUser('farm', $queries);
        }
        if ($populations === 'y') {
            upgradeUser('population', $queries);
        }

        // Handle card upgrades
        if ($upgradeCard === 'y') {
            $response = makeCurlRequest($queries, 'https://api.thevertus.app/upgrade-cards');

            if (isset($response['economyCards']) && $ekonomi === 'y') {
                upgradeCards($response['economyCards'], $queries);
            }
            if (isset($response['militaryCards']) && $militer === 'y') {
                upgradeCards($response['militaryCards'], $queries);
            }
            if (isset($response['scienceCards']) && $science === 'y') {
                upgradeCards($response['scienceCards'], $queries);
            }
        }
    } else {
        echo "User data not found in response. $response\n";
    }

    curl_close($ch);
    echo "==================================\n";
}
}
?>
