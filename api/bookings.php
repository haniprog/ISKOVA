<?php
require_once __DIR__ . '/config.php';

$input = readJsonInput();
$action = $_GET['action'] ?? $input['action'] ?? '';

switch ($action) {
    case 'getBookings':
        $userId = getCurrentUserId($conn);
        if (!$userId) {
            sendJson([]);
        }

        $stmt = $conn->prepare('SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        sendJson($result->fetch_all(MYSQLI_ASSOC));
        break;

    case 'getAllBookings':
        $stmt = $conn->prepare('SELECT * FROM bookings ORDER BY created_at DESC');
        $stmt->execute();
        $result = $stmt->get_result();
        sendJson($result->fetch_all(MYSQLI_ASSOC));
        break;

    case 'createBooking':
        $userId = requireLogin($conn);

        $lab = trim($input['lab'] ?? '');
        $date = trim($input['date'] ?? '');
        $time = trim($input['time'] ?? '');
        $timeOut = trim($input['time_out'] ?? $input['timeOut'] ?? '');
        $system = trim($input['system'] ?? '');

        if (!$lab || !$date || !$time || !$timeOut) {
            sendJson(['success' => false, 'error' => 'Missing booking details'], 400);
        }

        $stmt = $conn->prepare('INSERT INTO bookings (user_id, lab, date, time, time_out, system, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $status = 'pending';
        $stmt->bind_param('issssss', $userId, $lab, $date, $time, $timeOut, $system, $status);
        $stmt->execute();

        sendJson([
            'success' => true,
            'booking' => [
                'id' => $stmt->insert_id,
                'user_id' => $userId,
                'lab' => $lab,
                'date' => $date,
                'time' => $time,
                'time_out' => $timeOut,
                'system' => $system,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        break;

    case 'updateBookingStatus':
        $userId = requireLogin($conn);
        $bookingId = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        $status = trim($input['status'] ?? 'confirmed');

        $stmt = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ? AND user_id = ?');
        $stmt->bind_param('sii', $status, $bookingId, $userId);
        $stmt->execute();

        sendJson(['success' => $stmt->affected_rows > 0]);
        break;

    case 'deleteBooking':
        $userId = requireLogin($conn);
        $bookingId = (int) ($input['id'] ?? $_GET['id'] ?? 0);

        $stmt = $conn->prepare('DELETE FROM bookings WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $bookingId, $userId);
        $stmt->execute();

        sendJson(['success' => $stmt->affected_rows > 0]);
        break;

    case 'countPendingBookings':
        $userId = requireLogin($conn);
        $stmt = $conn->prepare('SELECT COUNT(*) AS count FROM bookings WHERE user_id = ? AND status = "pending"');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        sendJson(['count' => (int) ($row['count'] ?? 0)]);
        break;

    case 'expireOldPendingBookings':
        $userId = requireLogin($conn);
        $stmt = $conn->prepare('UPDATE bookings SET status = "expired" WHERE user_id = ? AND status = "pending" AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        sendJson(['success' => true]);
        break;

    case 'getLabs':
        $stmt = $conn->prepare('SELECT * FROM labs ORDER BY name ASC');
        $stmt->execute();
        $result = $stmt->get_result();
        $labs = $result->fetch_all(MYSQLI_ASSOC);

        if (empty($labs)) {
            sendJson([
                ['name' => 'Lab A', 'capacity' => 15, 'computers' => 15, 'type' => 'small', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab B', 'capacity' => 20, 'computers' => 20, 'type' => 'small', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab C', 'capacity' => 25, 'computers' => 25, 'type' => 'medium', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab D', 'capacity' => 30, 'computers' => 30, 'type' => 'medium', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab E', 'capacity' => 35, 'computers' => 35, 'type' => 'medium', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab F', 'capacity' => 40, 'computers' => 40, 'type' => 'medium', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab G', 'capacity' => 45, 'computers' => 45, 'type' => 'large', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab H', 'capacity' => 50, 'computers' => 50, 'type' => 'large', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab I', 'capacity' => 60, 'computers' => 60, 'type' => 'large', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor'],
                ['name' => 'Lab J', 'capacity' => 80, 'computers' => 80, 'type' => 'large', 'status' => 'available', 'building' => 'Southwing', 'floor' => '5th Floor']
            ]);
        }

        sendJson($labs);
        break;

    default:
        sendJson(['success' => false, 'error' => 'Unsupported action'], 400);
}
