<?php
// /HelpDesk_EQF/modules/dashboard/tasks/helpers/TaskEvents.php

function logTaskEvent(PDO $pdo, int $taskId, int $actorId, string $eventType, ?string $note = null, $oldValue = null, $newValue = null): void
{
    $stmt = $pdo->prepare("
        INSERT INTO task_events (task_id, actor_user_id, event_type, note, old_value, new_value)
        VALUES (:task_id, :actor_user_id, :event_type, :note, :old_value, :new_value)
    ");

    $old = is_null($oldValue) ? null : (is_string($oldValue) ? $oldValue : json_encode($oldValue, JSON_UNESCAPED_UNICODE));
    $new = is_null($newValue) ? null : (is_string($newValue) ? $newValue : json_encode($newValue, JSON_UNESCAPED_UNICODE));

    $stmt->execute([
        ':task_id' => $taskId,
        ':actor_user_id' => $actorId,
        ':event_type' => $eventType,
        ':note' => $note,
        ':old_value' => $old,
        ':new_value' => $new,
    ]);
}
