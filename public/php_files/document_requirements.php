<?php
/**
 * Shared definition of the documents required for a reservation.
 * Kept in one place so admin + owner backends and the payment
 * verification step all agree on the same set.
 *
 * If you ever need to add/remove a required document, this is the
 * only place you need to change it (plus a migration to add rows
 * for existing "requirements pending" reservations if needed).
 */
function defaultDocumentTemplates(): array {
    return [
        ['key' => 'valid_id_1', 'name' => 'Valid ID #1'],
        ['key' => 'valid_id_2', 'name' => 'Valid ID #2'],
        ['key' => 'tin_number', 'name' => 'TIN Number'],
        ['key' => 'reservation_agreement', 'name' => 'Reservation Agreement'],
    ];
}

/**
 * Creates the default document rows for a reservation, if they don't
 * already exist. Safe to call more than once (unique key on
 * reservation_id + document_key means duplicates are just skipped).
 */
function seedReservationDocuments(mysqli $conn, int $reservationId): void {
    $templates = defaultDocumentTemplates();

    $sql = "
        INSERT IGNORE INTO reservation_documents (reservation_id, document_key, document_name, status)
        VALUES (?, ?, ?, 'pending')
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        error_log('seedReservationDocuments failed to prepare: ' . $conn->error);
        return;
    }

    foreach ($templates as $doc) {
        $stmt->bind_param("iss", $reservationId, $doc['key'], $doc['name']);
        $stmt->execute();
    }

    $stmt->close();
}
