<?php
// /HelpDesk_EQF/config/audit.php

function audit_log(PDO $pdo, string $action, ?string $entity = null, $entityId = null, array $details = []): void {
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();

        $actorId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $actorName = trim(($_SESSION['user_name'] ?? '') . ' ' . ($_SESSION['user_last'] ?? '')) ?: null;
        $actorMail = $_SESSION['user_email'] ?? null;
        $actorRol  = isset($_SESSION['user_rol']) ? (int)$_SESSION['user_rol'] : null;
        $actorArea = $_SESSION['user_area'] ?? null;

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ip && strpos($ip, ',') !== false) $ip = trim(explode(',', $ip)[0]); // primer IP
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $st = $pdo->prepare("
            INSERT INTO audit_log
            (actor_user_id, actor_name, actor_email, actor_rol, actor_area,
             action, entity, entity_id, ip_address, user_agent, details)
            VALUES
            (:uid, :name, :email, :rol, :area,
             :action, :entity, :eid, :ip, :ua, :details)
        ");

        $st->execute([
            ':uid'     => $actorId,
            ':name'    => $actorName,
            ':email'   => $actorMail,
            ':rol'     => $actorRol,
            ':area'    => $actorArea,
            ':action'  => $action,
            ':entity'  => $entity,
            ':eid'     => ($entityId === null ? null : (string)$entityId),
            ':ip'      => $ip,
            ':ua'      => $ua ? mb_substr($ua, 0, 255) : null,
            ':details' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        ]);
    } catch (Throwable $e) {
        // No romper flujo por auditoría
    }
}
