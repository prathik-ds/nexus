<?php
include 'includes/header.php';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$stmt = $pdo->query("SELECT * FROM events ORDER BY category, name");
$events = $stmt->fetchAll();

// Get user's team memberships for team events
$myTeams = [];
$myRegistrations = []; // Individual registrations
if ($user) {
    $uid = $user['user_id'];

    // Fetch Teams
    $stmt2 = $pdo->prepare("
        SELECT t.event_id, t.id as team_id, t.name as team_name, t.invite_code, t.leader_user_id
        FROM teams t
        JOIN team_members tm ON t.id = tm.team_id
        WHERE tm.user_id = ?
    ");
    $stmt2->execute([$uid]);
    foreach ($stmt2->fetchAll() as $row) {
        $myTeams[$row['event_id']] = $row;
    }

    // Fetch Individual Registrations
    $stmt3 = $pdo->prepare("SELECT event_id FROM registrations WHERE user_id = ?");
    $stmt3->execute([$uid]);
    $myRegistrations = $stmt3->fetchAll(PDO::FETCH_COLUMN);
}
?>

<style>
    /* Override container width for events page to use full width */
    .container {
        max-width: 100% !important;
        padding: 0 40px !important;
    }

    /* Events grid */
    .events-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 32px;
        margin-top: 40px;
        margin-bottom: 80px;
    }

    /* Event card */
    .ev-card {
        background: rgba(15, 22, 41, 0.6);
        border: 1px solid rgba(100, 130, 200, 0.15);
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        transition: transform 0.35s ease, box-shadow 0.35s ease, border-color 0.35s ease;
        position: relative;
    }

    .ev-card:hover {
        transform: translateY(-8px);
        border-color: rgba(0, 212, 255, 0.35);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35), 0 0 30px rgba(0, 212, 255, 0.08);
    }

    .ev-card-img {
        height: 200px;
        position: relative;
        overflow: hidden;
        background: rgba(8, 12, 26, 0.8);
        flex-shrink: 0;
    }

    .ev-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .ev-card:hover .ev-card-img img {
        transform: scale(1.08);
    }

    .ev-card-img-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.15), rgba(0, 212, 255, 0.08));
    }

    .ev-card-overlay {
        position: absolute;
        inset: 0;
        background: linear-gradient(to bottom, transparent 40%, rgba(8, 12, 26, 0.85) 100%);
        pointer-events: none;
    }

    .ev-card-badge {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(8, 12, 26, 0.65);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        padding: 4px 12px;
        border-radius: 50px;
    }

    .ev-card-body {
        padding: 22px 24px 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .ev-card-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.3rem;
        font-weight: 700;
        color: #f0f4ff;
        letter-spacing: -0.01em;
        margin-bottom: 8px;
    }

    .ev-card-desc {
        color: #94a3c7;
        font-size: 0.84rem;
        line-height: 1.65;
        margin-bottom: 18px;
        flex: 1;
    }

    .ev-card-meta {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
    }

    .ev-card-meta-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.8rem;
        color: #94a3c7;
    }

    .ev-card-meta-item i {
        width: 16px;
        text-align: center;
    }

    .ev-cap-label {
        display: flex;
        justify-content: space-between;
        font-size: 0.7rem;
        color: #5b6a8a;
        margin-bottom: 5px;
    }

    .ev-cap-bar {
        width: 100%;
        height: 4px;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 4px;
        overflow: hidden;
    }

    .ev-cap-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s ease;
    }

    .ev-card-footer {
        padding: 0 24px 22px;
    }

    .ev-register-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px;
        background: transparent;
        border: 2px solid #00d4ff;
        color: #00d4ff;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .ev-register-btn:hover {
        background: rgba(0, 212, 255, 0.1);
        box-shadow: 0 0 20px rgba(0, 212, 255, 0.2);
        color: #fff;
    }

    .ev-details-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #94a3c7;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        margin-bottom: 12px;
    }

    .ev-details-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .ev-register-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        border-color: rgba(255, 255, 255, 0.15);
        color: #5b6a8a;
    }

    @media (max-width: 900px) {
        .events-grid {
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .container {
            padding: 0 16px !important;
        }
    }

    @media (max-width: 600px) {
        .container {
            padding: 0 8px !important;
            margin-top: 10px !important;
        }

        .events-grid {
            grid-template-columns: 1fr;
            gap: 12px;
            margin-bottom: 70px;
        }

        /* ── Compact Horizontal Card ── */
        .ev-card {
            display: grid !important;
            grid-template-columns: 110px 1fr !important;
            /* Shrunk image col width slightly to give more room to text */
            grid-template-rows: 1fr auto !important;
            grid-template-areas:
                "img body"
                "img footer" !important;
            height: 135px !important;
            /* Increased height for better fit */
            border-radius: 14px !important;
            background: linear-gradient(145deg, rgba(20, 28, 50, 0.9), rgba(8, 12, 26, 0.95)) !important;
            border: 1px solid rgba(0, 212, 255, 0.12) !important;
            overflow: hidden !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3) !important;
            transform: none !important;
        }

        .ev-card:hover {
            transform: none !important;
        }

        .ev-card-img {
            grid-area: img !important;
            width: 110px !important;
            height: 100% !important;
            border-radius: 14px 0 0 14px !important;
        }

        .ev-card-badge {
            top: 6px;
            left: 6px;
            font-size: 0.45rem;
            padding: 2px 8px;
            backdrop-filter: blur(4px);
        }

        .ev-team-badge {
            display: none !important;
            /* Hide team badge from image to save space */
        }

        /* Reposition Eligibility to bottom of image on mobile */
        .ev-card-img div[style*="left: 50%"] {
            top: auto !important;
            bottom: 6px !important;
            transform: translateX(-50%) !important;
            font-size: 0.42rem !important;
            padding: 2px 6px !important;
            width: 85% !important;
            text-align: center;
        }

        .ev-card-body {
            grid-area: body !important;
            padding: 12px 12px 2px 15px !important;
            /* Increased left padding (15px) for clear gutter from image */
            display: flex !important;
            flex-direction: column !important;
            justify-content: flex-start !important;
        }

        .ev-card-title {
            font-size: 0.95rem !important;
            margin-bottom: 4px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            color: #fff !important;
        }

        .ev-card-desc {
            display: none !important;
            /* Hide description to enable compact mode */
        }

        .ev-card-meta {
            margin-bottom: 4px !important;
            gap: 2px !important;
            flex-direction: row !important;
            flex-wrap: wrap !important;
        }

        .ev-card-meta-item {
            font-size: 0.6rem !important;
            padding: 1px 4px !important;
            background: transparent !important;
            border: none !important;
            color: #94a3c7 !important;
        }

        .ev-card-meta-item i {
            width: 12px !important;
            font-size: 0.65rem !important;
        }

        /* Hide Capacity Bar on small mobile cards */
        div[style*="margin-bottom: 4px"] {
            display: none !important;
        }

        .ev-card-footer {
            grid-area: footer !important;
            padding: 4px 12px 12px 15px !important;
            /* Balanced left padding with body */
            display: flex !important;
            gap: 10px !important;
            background: transparent !important;
            align-items: center !important;
            justify-content: flex-start !important;
        }

        .ev-details-btn {
            width: 36px !important;
            height: 36px !important;
            min-height: 36px !important;
            margin: 0 !important;
            padding: 0 !important;
            border-radius: 10px !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: rgba(255, 255, 255, 0.08) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            overflow: hidden !important;
        }

        .ev-details-btn .btn-text {
            display: none !important;
            /* Cleanly hide text on mobile */
        }

        .ev-details-btn i {
            margin: 0 !important;
            font-size: 1.25rem !important;
            /* Slightly larger for better detail */
            color: #fff !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .ev-register-btn {
            height: 36px !important;
            min-height: 36px !important;
            font-size: 0.68rem !important;
            padding: 0 16px !important;
            margin: 0 !important;
            flex: 1 !important;
            border-radius: 10px !important;
            letter-spacing: 0.5px !important;
            white-space: nowrap !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
        }

        .ev-register-btn i {
            font-size: 0.75rem !important;
        }

        /* Modal Mobile Shrink */
        .details-modal-box {
            width: 94% !important;
            max-height: 85vh !important;
            border-radius: 20px !important;
        }

        .details-header-img {
            height: 160px !important;
        }

        .details-body {
            padding: 20px !important;
        }

        .team-modal-box {
            padding: 24px 20px !important;
            border-radius: 20px !important;
        }

        .team-tab {
            font-size: 0.65rem !important;
            padding: 8px !important;
        }

        .team-code-box {
            font-size: 1.2rem !important;
            padding: 12px !important;
        }
    }

    /* ── Team Badge ── */
    .ev-team-badge {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(124, 58, 237, 0.55);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(124, 58, 237, 0.5);
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 800;
        color: white;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    .ev-register-btn.team-btn {
        border-color: #a855f7;
        color: #a855f7;
    }

    .ev-register-btn.team-btn:hover {
        background: rgba(168, 85, 247, 0.1);
        box-shadow: 0 0 20px rgba(168, 85, 247, 0.2);
        color: #fff;
    }

    .ev-register-btn.joined-btn {
        border-color: #10b981;
        color: #10b981;
        cursor: default;
        pointer-events: none;
        opacity: 0.85;
    }

    /* ── Team Modal ── */
    #teamModal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(4, 6, 14, 0.85);
        z-index: 3000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(10px);
    }

    .team-modal-box {
        background: rgba(15, 22, 41, 0.97);
        border: 1px solid rgba(124, 58, 237, 0.25);
        border-radius: 24px;
        padding: 36px;
        max-width: 420px;
        width: 92%;
        position: relative;
        animation: fadeInUp 0.3s ease;
    }

    .team-tab-bar {
        display: flex;
        gap: 10px;
        margin-bottom: 28px;
    }

    .team-tab {
        flex: 1;
        padding: 10px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: transparent;
        color: #94a3c7;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.78rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.25s;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .team-tab.active {
        background: rgba(124, 58, 237, 0.15);
        border-color: rgba(124, 58, 237, 0.4);
        color: #a855f7;
    }

    .team-panel {
        display: none;
    }

    .team-panel.active {
        display: block;
    }

    .team-input {
        width: 100%;
        padding: 12px 16px;
        background: rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #f0f4ff;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.9rem;
        box-sizing: border-box;
        margin-bottom: 14px;
        transition: border-color 0.25s;
    }

    .team-input:focus {
        outline: none;
        border-color: rgba(124, 58, 237, 0.5);
    }

    .team-input::placeholder {
        color: #5b6a8a;
    }

    .team-submit-btn {
        width: 100%;
        padding: 13px;
        background: linear-gradient(135deg, rgba(124, 58, 237, 0.8), rgba(168, 85, 247, 0.8));
        border: none;
        border-radius: 12px;
        color: white;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        cursor: pointer;
        transition: opacity 0.25s;
    }

    .team-submit-btn:hover {
        opacity: 0.85;
    }

    .team-submit-btn:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }

    .team-alert {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 0.82rem;
        margin-bottom: 16px;
        display: none;
    }

    .team-alert.success {
        background: rgba(16, 185, 129, 0.08);
        border: 1px solid rgba(16, 185, 129, 0.2);
        color: #10b981;
    }

    .team-alert.error {
        background: rgba(244, 63, 94, 0.08);
        border: 1px solid rgba(244, 63, 94, 0.2);
        color: #f43f5e;
    }

    .team-code-box {
        font-family: 'JetBrains Mono', monospace;
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: 6px;
        text-align: center;
        padding: 18px;
        background: rgba(124, 58, 237, 0.08);
        border: 2px dashed rgba(124, 58, 237, 0.35);
        border-radius: 14px;
        color: #a855f7;
        margin: 16px 0;
        cursor: pointer;
        transition: background 0.2s;
    }

    .team-code-box:hover {
        background: rgba(124, 58, 237, 0.14);
    }

    .team-member-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 0.78rem;
        color: #94a3c7;
        margin: 4px;
    }

    .team-member-chip.leader {
        border-color: rgba(251, 191, 36, 0.3);
        color: #fbbf24;
    }

    .team-info-section {
        margin-top: 20px;
    }

    .team-size-hint {
        font-size: 0.75rem;
        color: #5b6a8a;
        text-align: center;
        margin-top: 8px;
    }

    /* ── Details Modal ── */
    #detailsModal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(4, 6, 14, 0.92);
        z-index: 4000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(15px);
    }

    .details-modal-box {
        background: rgba(15, 22, 41, 0.98);
        border: 1px solid rgba(0, 212, 255, 0.2);
        border-radius: 28px;
        padding: 0;
        max-width: 650px;
        width: 95%;
        max-height: 90vh;
        position: relative;
        overflow-y: auto;
        animation: zoomIn 0.3s ease;
    }

    .details-header-img {
        height: 240px;
        width: 100%;
        object-fit: cover;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .details-body {
        padding: 32px;
    }

    .details-rules-box {
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 16px;
        padding: 20px;
        margin-top: 24px;
    }

    @keyframes zoomIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* ── Confirmation Modal (Think Twice) ── */
    #confirmRegisterModal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(4, 6, 14, 0.9);
        z-index: 5000;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }

    .confirm-modal-box {
        background: rgba(15, 22, 41, 0.98);
        border: 1px solid rgba(255, 170, 0, 0.25);
        border-radius: 24px;
        padding: 40px 36px 32px;
        max-width: 420px;
        width: 92%;
        position: relative;
        animation: zoomIn 0.3s ease;
        text-align: center;
        box-shadow: 0 0 60px rgba(255, 170, 0, 0.06), 0 0 120px rgba(0, 0, 0, 0.4);
    }

    .confirm-modal-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: rgba(255, 170, 0, 0.08);
        border: 2px solid rgba(255, 170, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        animation: confirmPulse 2s ease-in-out infinite;
    }

    .confirm-modal-icon i {
        font-size: 2rem;
        color: #ffaa00;
    }

    @keyframes confirmPulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(255, 170, 0, 0.15); }
        50% { box-shadow: 0 0 0 12px rgba(255, 170, 0, 0); }
    }

    .confirm-modal-title {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.4rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 8px;
        letter-spacing: -0.01em;
    }

    .confirm-modal-subtitle {
        font-size: 0.88rem;
        color: #94a3c7;
        margin-bottom: 20px;
        line-height: 1.6;
    }

    .confirm-modal-warning {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(244, 63, 94, 0.06);
        border: 1px solid rgba(244, 63, 94, 0.18);
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 28px;
        text-align: left;
    }

    .confirm-modal-warning i {
        color: #f43f5e;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .confirm-modal-warning span {
        font-size: 0.78rem;
        color: #f0a0b0;
        font-weight: 600;
        line-height: 1.5;
    }

    .confirm-event-name {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #00d4ff;
        background: rgba(0, 212, 255, 0.06);
        border: 1px solid rgba(0, 212, 255, 0.12);
        border-radius: 10px;
        padding: 10px 16px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .confirm-modal-actions {
        display: flex;
        gap: 12px;
    }

    .confirm-btn-cancel {
        flex: 1;
        padding: 13px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #94a3c7;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .confirm-btn-cancel:hover {
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 255, 255, 0.2);
        color: #fff;
    }

    .confirm-btn-proceed {
        flex: 1.3;
        padding: 13px;
        background: linear-gradient(135deg, rgba(0, 212, 255, 0.2), rgba(124, 58, 237, 0.2));
        border: 1px solid rgba(0, 212, 255, 0.3);
        border-radius: 12px;
        color: #00d4ff;
        font-family: 'Space Grotesk', sans-serif;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .confirm-btn-proceed:hover {
        background: linear-gradient(135deg, rgba(0, 212, 255, 0.3), rgba(124, 58, 237, 0.3));
        border-color: rgba(0, 212, 255, 0.5);
        color: #fff;
        box-shadow: 0 0 25px rgba(0, 212, 255, 0.15);
    }

    @media (max-width: 600px) {
        .confirm-modal-box {
            padding: 28px 20px 24px;
            border-radius: 20px;
        }

        .confirm-modal-title {
            font-size: 1.15rem;
        }

        .confirm-modal-actions {
            flex-direction: column-reverse;
            gap: 10px;
        }
    }
</style>

<!-- Page Header -->
<div style="margin-bottom: 10px; text-align: center; padding-top: 10px;">
    <div
        style="display: inline-flex; align-items: center; gap: 8px; background: rgba(0,212,255,0.06); border: 1px solid rgba(0,212,255,0.15); padding: 6px 16px; border-radius: 50px; margin-bottom: 16px;">
        <i class="fa-solid fa-trophy" style="font-size: 0.7rem; color: var(--accent-1);"></i>
        <span
            style="font-size: 0.7rem; font-weight: 700; color: var(--accent-1); text-transform: uppercase; letter-spacing: 2px;">Live
            Events</span>
    </div>
    <h1
        style="font-family: 'Space Grotesk', sans-serif; font-size: 3rem; font-weight: 700; letter-spacing: -0.02em; margin-bottom: 10px;">
        <span
            style="background: var(--grad-primary); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">Competitions</span>
    </h1>
    <p style="color: var(--text-secondary); max-width: 400px; margin: 0 auto; font-size: 0.9rem;">Choose your challenge
        and register to compete at Nexus Fest 2026</p>
</div>

<?php if ($success): ?>
    <div
        style="padding: 14px 20px; border-radius: 14px; border: 1px solid rgba(16,185,129,0.2); background: rgba(16,185,129,0.06); color: var(--text-primary); margin-bottom: 20px; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-check" style="color: var(--success);"></i>
        Registration Successful! View your events in the <a href="dashboard.php"
            style="color: var(--accent-2); font-weight: 700; text-decoration: none;">Dashboard →</a>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div
        style="padding: 14px 20px; border-radius: 14px; border: 1px solid rgba(244,63,94,0.2); background: rgba(244,63,94,0.06); color: var(--danger); margin-bottom: 20px; font-size: 0.88rem; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Events Grid -->
<div class="events-grid">
    <?php foreach ($events as $index => $event): ?>
        <?php
        $fill = $event['max_participants'] > 0 ? ($event['current_participants'] / $event['max_participants']) * 100 : 0;
        $is_full = $event['current_participants'] >= $event['max_participants'];

        $cat_color = '#00d4ff'; // IT = Water (Cyan)
        if ($event['category'] == 'Commerce')
            $cat_color = '#ff5722'; // Commerce = Flame (Orange/Red)
        if ($event['category'] == 'ART')
            $cat_color = '#22c55e'; // Art = Forest (Green)
    
        $cap_color = $fill > 80 ? 'var(--danger)' : 'linear-gradient(90deg, var(--accent-2), var(--accent-1))';
        $is_team = !empty($event['is_team_event']);
        $my_team = $myTeams[$event['id']] ?? null;
        ?>
        <div class="ev-card" style="animation: fadeInUp <?= 0.2 + ($index * 0.07) ?>s ease forwards; opacity: 0;">

            <!-- Image Header -->
            <div class="ev-card-img">
                <?php if (!empty($event['image'])): ?>
                    <img src="<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['name']) ?>"
                        loading="lazy">
                <?php else: ?>
                    <div class="ev-card-img-placeholder">
                        <i class="fa-solid fa-microchip" style="font-size: 3.5rem; color: <?= $cat_color ?>; opacity: 0.3;"></i>
                    </div>
                <?php endif; ?>
                <div class="ev-card-overlay"></div>
                <div class="ev-card-badge">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: <?= $cat_color ?>;"></span>
                    <span
                        style="font-size: 0.62rem; font-weight: 800; color: white; text-transform: uppercase; letter-spacing: 1.5px;"><?= htmlspecialchars($event['category']) ?></span>
                </div>
                <?php if ($is_team): ?>
                    <div class="ev-team-badge">
                        <i class="fa-solid fa-users" style="font-size: 0.55rem;"></i> TEAM
                    </div>
                <?php endif; ?>

                <!-- Eligibility Overlay (Centered at the top of the image) -->
                <?php $el_stream = $event['eligibility_stream'] ?? 'ALL'; ?>
                <div
                    style="position: absolute; top: 14px; left: 50%; transform: translateX(-50%); z-index: 2; padding: 4px 12px; border-radius: 6px; font-size: 0.58rem; font-weight: 800; letter-spacing: 1px; background: rgba(0,0,0,0.65); color: <?= $el_stream == 'ALL' ? '#10b981' : '#fbbf24' ?>; border: 1px solid currentColor; backdrop-filter: blur(10px); white-space: nowrap; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <?= $el_stream == 'ALL' ? 'OPEN TO ALL' : strtoupper($el_stream) . ' ONLY' ?>
                </div>

            </div>

            <!-- Card Body -->
            <div class="ev-card-body">
                <h3 class="ev-card-title"><?= htmlspecialchars($event['name']) ?></h3>
                <p class="ev-card-desc">
                    <?= htmlspecialchars($event['description'] ?: 'Challenge yourself in the ' . $event['name'] . ' competition track at Nexus Fest 2026.') ?>
                </p>

                <!-- Meta Info -->
                <div class="ev-card-meta">
                    <div class="ev-card-meta-item">
                        <i class="fa-regular fa-calendar" style="color: var(--accent-1);"></i>
                        <?= date('d M Y', strtotime($event['date'])) ?> · <?= date('h:i A', strtotime($event['time'])) ?>
                    </div>
                    <div class="ev-card-meta-item">
                        <i class="fa-solid fa-location-dot" style="color: var(--accent-2);"></i>
                        <?= htmlspecialchars($event['venue']) ?>
                    </div>
                    <?php if ($is_team): ?>
                        <div class="ev-card-meta-item">
                            <i class="fa-solid fa-users" style="color: #a855f7;"></i>
                            Team · <?= $event['min_team_size'] ?>–<?= $event['max_team_size'] ?> members
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Capacity Bar -->
                <div style="margin-bottom: 4px;">
                    <div class="ev-cap-label">
                        <span>Capacity</span>
                        <span style="color: var(--text-secondary); font-weight: 700;"><?= $event['current_participants'] ?>
                            / <?= $event['max_participants'] ?></span>
                    </div>
                    <div class="ev-cap-bar">
                        <div class="ev-cap-fill" style="width: <?= $fill ?>%; background: <?= $cap_color ?>;"></div>
                    </div>
                </div>
            </div>

            <!-- Action Footer -->
            <div class="ev-card-footer">
                <button class="ev-details-btn" onclick='showEventDetails(<?= json_encode($event) ?>)'>
                    <i class="fa-solid fa-circle-info"></i> <span class="btn-text">More Details</span>
                </button>
                <?php if ($user): ?>
                    <?php if ($is_team): ?>
                        <?php if ($my_team): ?>
                            <!-- Already in a team -->
                            <button class="ev-register-btn joined-btn"
                                onclick="viewMyTeam(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['name'])) ?>')"
                                style="cursor:pointer; pointer-events:all;">
                                <i class="fa-solid fa-users"></i> My Team: <?= htmlspecialchars($my_team['team_name']) ?>
                            </button>
                        <?php elseif ($is_full): ?>
                            <button class="ev-register-btn" disabled>
                                <i class="fa-solid fa-ban"></i> Full House
                            </button>
                        <?php else: ?>
                            <button class="ev-register-btn team-btn"
                                onclick="showConfirmRegister(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['name'])) ?>', true, <?= (int) $event['min_team_size'] ?>, <?= (int) $event['max_team_size'] ?>)">
                                <i class="fa-solid fa-users"></i> Team Register
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if (in_array($event['id'], $myRegistrations)): ?>
                            <!-- Already Registered Individually - Unregister Removed per Request -->
                            <button type="button" class="ev-register-btn" disabled
                                style="border-color: var(--success); color: var(--success); background: rgba(16, 185, 129, 0.05); opacity: 1;">
                                <i class="fa-solid fa-check-circle"></i> Registered
                            </button>
                        <?php else: ?>
                            <form id="regForm-<?= $event['id'] ?>" action="register_event.php" method="POST">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <?php if ($is_full): ?>
                                    <button type="button" class="ev-register-btn" disabled>
                                        <i class="fa-solid fa-ban"></i> Full House
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="ev-register-btn" onclick="showConfirmRegister(<?= $event['id'] ?>, '<?= htmlspecialchars(addslashes($event['name'])) ?>')">
                                        <i class="fa-solid fa-bolt"></i> Register Now
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="ev-register-btn">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Register
                    </a>
                <?php endif; ?>
            </div>

        </div>
    <?php endforeach; ?>
</div>

<!-- ═══ TEAM MODAL ═══ -->
<div id="teamModal">
    <div class="team-modal-box">
        <!-- Header -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <div>
                <h3 id="tModal-eventName"
                    style="font-family:'Space Grotesk',sans-serif; font-size:1.1rem; font-weight:700; color:#a855f7; margin:0;">
                    Team Register</h3>
                <div id="tModal-teamMeta" style="font-size:0.72rem; color:#5b6a8a; margin-top:4px;"></div>
            </div>
            <button onclick="closeTeamModal()"
                style="background:rgba(244,63,94,0.08); border:1px solid rgba(244,63,94,0.15); color:#f43f5e; cursor:pointer; width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1rem;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="tModal-alert" class="team-alert"></div>

        <!-- Tab view: create / join -->
        <div id="tModal-tabs">
            <div class="team-tab-bar">
                <button class="team-tab active" id="tab-create" onclick="switchTab('create')"><i
                        class="fa-solid fa-wand-magic-sparkles"></i> Create Team</button>
                <button class="team-tab" id="tab-join" onclick="switchTab('join')"><i class="fa-solid fa-link"></i> Join
                    Team</button>
            </div>

            <!-- Create Panel -->
            <div class="team-panel active" id="panel-create">
                <input class="team-input" id="tInput-teamName" type="text" placeholder="Team name (e.g. Dark Matter)"
                    maxlength="50">
                <div id="tModal-sizeHint" class="team-size-hint"></div>
                <button class="team-submit-btn" id="btn-createTeam" onclick="doCreateTeam()">
                    <i class="fa-solid fa-rocket"></i> Create & Get Invite Code
                </button>
            </div>

            <!-- Join Panel -->
            <div class="team-panel" id="panel-join">
                <input class="team-input" id="tInput-inviteCode" type="text" placeholder="Enter 6-character invite code"
                    maxlength="10"
                    style="text-transform:uppercase; letter-spacing:4px; font-family:'JetBrains Mono',monospace; text-align:center; font-size:1.2rem;">
                <button class="team-submit-btn" id="btn-joinTeam" onclick="doJoinTeam()">
                    <i class="fa-solid fa-right-to-bracket"></i> Join Team
                </button>
            </div>
        </div>

        <!-- Created team info (after creation) -->
        <div id="tModal-created" style="display:none;">
            <div style="text-align:center; padding:10px 0 4px;">
                <i class="fa-solid fa-party-horn" style="font-size:2.5rem; color:#a855f7; margin-bottom:12px;"></i>
                <p style="color:#94a3c7; font-size:0.85rem; margin-bottom:4px;">Team created! Share this code with
                    teammates:</p>
                <div class="team-code-box" id="tModal-code" onclick="copyCode()" title="Click to copy"></div>
                <p style="font-size:0.7rem; color:#5b6a8a; margin-top:6px;"><i class="fa-solid fa-copy"></i> Click code
                    to copy · Share with your teammates</p>
            </div>
            <div class="team-info-section" id="tModal-memberList"></div>
        </div>

        <!-- View existing team -->
        <div id="tModal-myTeam" style="display:none;">
            <div style="text-align:center; margin-bottom:16px;">
                <i class="fa-solid fa-shield-halved" style="font-size:2rem; color:#a855f7;"></i>
                <h4 id="tMyTeam-name" style="font-family:'Space Grotesk',sans-serif; margin:12px 0 4px; color:#f0f4ff;">
                </h4>
            </div>
            <div style="font-size:0.7rem; color:#5b6a8a; text-align:center; margin-bottom:6px;">INVITE CODE</div>
            <div class="team-code-box" id="tMyTeam-code" onclick="copyMyCode()" title="Click to copy"></div>
            <p style="font-size:0.7rem; color:#5b6a8a; text-align:center; margin-top:4px;"><i
                    class="fa-solid fa-copy"></i> Click to copy</p>
            <div class="team-info-section" id="tMyTeam-members"></div>
            <!-- Leave Team Feature Removed per Request -->
        </div>
    </div>
</div>
<!-- ═══ DETAILS MODAL ═══ -->
<div id="detailsModal">
    <div class="details-modal-box">
        <button onclick="closeDetailsModal()"
            style="position:absolute; top:20px; right:20px; z-index:10; background:rgba(0,0,0,0.5); border:1px solid rgba(255,255,255,0.2); color:white; cursor:pointer; width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.2rem; backdrop-filter:blur(5px);">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <img id="det-img" class="details-header-img" src="" alt="Event Logo" style="display:none;">
        <div id="det-placeholder"
            style="height:120px; background:linear-gradient(135deg, rgba(0,212,255,0.1), rgba(124,58,237,0.1)); display:none;">
        </div>

        <div class="details-body">
            <div id="det-cat"
                style="font-size:0.7rem; font-weight:800; color:var(--accent-1); text-transform:uppercase; letter-spacing:2px; margin-bottom:8px;">
            </div>
            <h2 id="det-name"
                style="font-family:'Space Grotesk',sans-serif; font-size:2rem; font-weight:700; color:#fff; margin:0 0 16px;">
            </h2>

            <div
                style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; padding-bottom:24px; border-bottom:1px solid rgba(255,255,255,0.1);">
                <div>
                    <div style="font-size:0.65rem; color:#5b6a8a; text-transform:uppercase; margin-bottom:5px;">Schedule
                    </div>
                    <div id="det-time" style="font-size:0.95rem; color:#f0f4ff; font-weight:600;"></div>
                </div>
                <div>
                    <div style="font-size:0.65rem; color:#5b6a8a; text-transform:uppercase; margin-bottom:5px;">Venue
                    </div>
                    <div id="det-venue" style="font-size:0.95rem; color:#f0f4ff; font-weight:600;"></div>
                </div>
            </div>

            <p id="det-desc" style="color:#94a3c7; line-height:1.7; font-size:0.95rem;"></p>

            <div class="details-rules-box">
                <h4
                    style="font-family:'Space Grotesk',sans-serif; color:var(--accent-2); margin:0 0 12px; font-size:1rem; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-scroll"></i> Competition Rules
                </h4>
                <div id="det-rules" style="color:#f0f4ff; font-size:0.85rem; line-height:1.8; white-space:pre-wrap;">
                </div>
            </div>

            <div style="margin-top:30px; display:flex; align-items:center; gap:12px; color:#5b6a8a; font-size:0.8rem;">
                <i class="fa-solid fa-user-tie"></i>
                Coordinator: <span id="det-coord" style="color:#f0f4ff; font-weight:600;"></span>
            </div>
        </div>
    </div>
</div>

<!-- ═══ CONFIRM REGISTRATION MODAL ═══ -->
<div id="confirmRegisterModal">
    <div class="confirm-modal-box">
        <div class="confirm-modal-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="confirm-modal-title">Think Twice!</div>
        <div class="confirm-modal-subtitle">You are about to register for this event. Please make sure you are ready to commit.</div>
        <div class="confirm-event-name">
            <i class="fa-solid fa-bolt"></i>
            <span id="confirm-event-label"></span>
        </div>
        <div class="confirm-modal-warning">
            <i class="fa-solid fa-lock"></i>
            <span>Once registered, you <strong>cannot unregister</strong>. This action is permanent and irreversible.</span>
        </div>
        <div class="confirm-modal-actions">
            <button class="confirm-btn-cancel" onclick="closeConfirmModal()">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </button>
            <button class="confirm-btn-proceed" id="confirm-proceed-btn" onclick="proceedRegistration()">
                <i class="fa-solid fa-check-circle"></i> Yes, Register Me
            </button>
        </div>
    </div>
</div>

<script>
    let _activeEventId = null;
    let _activeTeamId = null;
    let _isLeader = false;
    let _myCode = '';

    function openTeamModal(eventId, eventName, minSize, maxSize) {
        _activeEventId = eventId;
        _activeTeamId = null;
        document.getElementById('teamModal').style.display = 'flex';
        document.getElementById('tModal-eventName').textContent = eventName;
        document.getElementById('tModal-teamMeta').textContent = `Team size: ${minSize}–${maxSize} members`;
        document.getElementById('tModal-tabs').style.display = 'block';
        document.getElementById('tModal-created').style.display = 'none';
        document.getElementById('tModal-myTeam').style.display = 'none';
        document.getElementById('tInput-teamName').value = '';
        document.getElementById('tInput-inviteCode').value = '';
        document.getElementById('tModal-sizeHint').textContent = `Min ${minSize} · Max ${maxSize} members per team`;
        clearAlert();
        switchTab('create');
    }

    function viewMyTeam(eventId, eventName) {
        _activeEventId = eventId;
        document.getElementById('teamModal').style.display = 'flex';
        document.getElementById('tModal-eventName').textContent = eventName;
        document.getElementById('tModal-tabs').style.display = 'none';
        document.getElementById('tModal-created').style.display = 'none';
        document.getElementById('tModal-myTeam').style.display = 'none';
        clearAlert();
        loadMyTeam(eventId);
    }

    function closeTeamModal() {
        document.getElementById('teamModal').style.display = 'none';
    }

    function switchTab(tab) {
        document.getElementById('tab-create').classList.toggle('active', tab === 'create');
        document.getElementById('tab-join').classList.toggle('active', tab === 'join');
        document.getElementById('panel-create').classList.toggle('active', tab === 'create');
        document.getElementById('panel-join').classList.toggle('active', tab === 'join');
        clearAlert();
    }

    function showAlert(msg, type) {
        const el = document.getElementById('tModal-alert');
        el.textContent = msg;
        el.className = `team-alert ${type}`;
        el.style.display = 'block';
    }
    function clearAlert() { document.getElementById('tModal-alert').style.display = 'none'; }

    async function doCreateTeam() {
        const name = document.getElementById('tInput-teamName').value.trim();
        if (!name) { showAlert('Enter a team name', 'error'); return; }

        const btn = document.getElementById('btn-createTeam');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';
        clearAlert();

        const fd = new FormData();
        fd.append('action', 'create_team');
        fd.append('event_id', _activeEventId);
        fd.append('team_name', name);

        const res = await fetch('team_actions.php', { method: 'POST', body: fd }).then(r => r.json());

        btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-rocket"></i> Create & Get Invite Code';

        if (res.success) {
            _myCode = res.team_code;
            _activeTeamId = res.team_id;
            document.getElementById('tModal-code').textContent = res.team_code;
            document.getElementById('tModal-tabs').style.display = 'none';
            document.getElementById('tModal-created').style.display = 'block';
            document.getElementById('tModal-memberList').innerHTML = `<p style="color:#5b6a8a;font-size:0.8rem;text-align:center;margin-top:8px;">Share the code — members will join and appear here after page refresh.</p>`;
        } else {
            showAlert(res.message, 'error');
        }
    }

    async function doJoinTeam() {
        const code = document.getElementById('tInput-inviteCode').value.trim().toUpperCase();
        if (!code) { showAlert('Enter an invite code', 'error'); return; }

        const btn = document.getElementById('btn-joinTeam');
        btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Joining...';
        clearAlert();

        const fd = new FormData();
        fd.append('action', 'join_team');
        fd.append('invite_code', code);

        const res = await fetch('team_actions.php', { method: 'POST', body: fd }).then(r => r.json());

        btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i> Join Team';

        if (res.success) {
            showAlert('✓ ' + res.message + ' — refreshing...', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(res.message, 'error');
        }
    }

    async function loadMyTeam(eventId) {
        const fd = new FormData();
        fd.append('action', 'get_team');
        fd.append('event_id', eventId);

        const res = await fetch('team_actions.php', { method: 'POST', body: fd }).then(r => r.json());

        if (res.success) {
            const t = res.team;
            _activeTeamId = t.id;
            _myCode = t.invite_code;
            _isLeader = (t.leader_user_id === '<?= $user ? $user['user_id'] : '' ?>');

            document.getElementById('tMyTeam-name').textContent = t.name;
            document.getElementById('tMyTeam-code').textContent = t.invite_code;
            document.getElementById('tModal-teamMeta').textContent = t.members.length + ' member(s) · ' + (t.members.length < 2 ? 'Waiting for teammates…' : 'Ready to compete!');

            let membersHtml = '<div style="font-size:0.7rem;color:#5b6a8a;margin-bottom:10px;text-transform:uppercase;letter-spacing:1.5px;">Members</div><div>';
            t.members.forEach(m => {
                const isL = m.user_id === t.leader_user_id;
                membersHtml += `<span class="team-member-chip ${isL ? 'leader' : ''}"><i class="fa-solid fa-${isL ? 'crown' : 'user'}"></i>${m.user_name}${isL ? ' (Leader)' : ''}</span>`;
            });
            membersHtml += '</div>';
            document.getElementById('tMyTeam-members').innerHTML = membersHtml;

            const leaveLabel = document.getElementById('leaveLabel');
            leaveLabel.textContent = _isLeader ? 'Dissolve Team' : 'Leave Team';
            if (_isLeader) {
                document.getElementById('btn-leaveTeam').style.background = 'rgba(244,63,94,0.2)';
            }

            document.getElementById('tModal-myTeam').style.display = 'block';
        } else {
            showAlert('Could not load team info', 'error');
        }
    }

    // leaveTeam logic removed.

    function copyCode() {
        navigator.clipboard.writeText(_myCode).then(() => {
            const el = document.getElementById('tModal-code');
            const old = el.textContent;
            el.textContent = 'Copied!';
            setTimeout(() => el.textContent = old, 1200);
        });
    }
    function copyMyCode() {
        navigator.clipboard.writeText(_myCode).then(() => {
            const el = document.getElementById('tMyTeam-code');
            const old = el.textContent;
            el.textContent = 'Copied!';
            setTimeout(() => el.textContent = old, 1200);
        });
    }

    async function doUnregister(eventId, eventName) {
        if (!confirm('Are you sure you want to unregister from ' + eventName + '?')) return;

        const fd = new FormData();
        fd.append('event_id', eventId);
        fd.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

        try {
            const res = await fetch('ajax_unregister.php', { method: 'POST', body: fd }).then(r => r.json());
            if (res.success) {
                // Reload same page to update UI without redirecting to dashboard
                location.reload();
            } else {
                alert(res.message);
            }
        } catch (e) {
            alert('An error occurred. Please try again.');
        }
    }

    // Close modal on backdrop click
    document.getElementById('teamModal').addEventListener('click', function (e) {
        if (e.target === this) closeTeamModal();
    });

    function showEventDetails(ev) {
        document.getElementById('det-name').textContent = ev.name;
        document.getElementById('det-cat').textContent = ev.category + ' TRACK';
        document.getElementById('det-desc').textContent = ev.description || 'No description available.';
        document.getElementById('det-rules').textContent = ev.rules || 'Rules will be shared soon.';
        document.getElementById('det-time').textContent = ev.date + ' at ' + ev.time;
        document.getElementById('det-venue').textContent = ev.venue || 'TBD';
        document.getElementById('det-coord').textContent = ev.coordinator_name || 'System Assigned';

        const img = document.getElementById('det-img');
        const ph = document.getElementById('det-placeholder');
        if (ev.image) {
            img.src = ev.image;
            img.style.display = 'block';
            ph.style.display = 'none';
        } else {
            img.style.display = 'none';
            ph.style.display = 'block';
        }

        document.getElementById('detailsModal').style.display = 'flex';
    }

    function closeDetailsModal() {
        document.getElementById('detailsModal').style.display = 'none';
    }

    document.getElementById('detailsModal').addEventListener('click', function (e) {
        if (e.target === this) closeDetailsModal();
    });

    // ── Confirm Registration Modal ──
    let _confirmEventId = null;
    let _confirmIsTeam = false;
    let _confirmTeamMin = 0;
    let _confirmTeamMax = 0;
    let _confirmEventName = '';

    function showConfirmRegister(eventId, eventName, isTeam, minSize, maxSize) {
        _confirmEventId = eventId;
        _confirmEventName = eventName;
        _confirmIsTeam = isTeam || false;
        _confirmTeamMin = minSize || 0;
        _confirmTeamMax = maxSize || 0;
        document.getElementById('confirm-event-label').textContent = eventName;
        document.getElementById('confirmRegisterModal').style.display = 'flex';
    }

    function closeConfirmModal() {
        document.getElementById('confirmRegisterModal').style.display = 'none';
        _confirmEventId = null;
        _confirmIsTeam = false;
    }

    function proceedRegistration() {
        if (_confirmEventId) {
            if (_confirmIsTeam) {
                // Capture details before closing
                const eid = _confirmEventId;
                const ename = _confirmEventName;
                const emin = _confirmTeamMin;
                const emax = _confirmTeamMax;

                closeConfirmModal();
                openTeamModal(eid, ename, emin, emax);
                return;
            } else {
                const form = document.getElementById('regForm-' + _confirmEventId);
                if (form) {
                    form.submit();
                }
            }
        }
        closeConfirmModal();
    }

    document.getElementById('confirmRegisterModal').addEventListener('click', function (e) {
        if (e.target === this) closeConfirmModal();
    });
</script>

<?php include 'includes/footer.php'; ?>