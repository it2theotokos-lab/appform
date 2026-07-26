<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Εγκατάσταση AppForm</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --text-color: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --success: #22c55e;
            --danger: #ef4444;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .container {
            width: 100%;
            max-width: 600px;
            padding: 20px;
        }
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        h2 {
            margin-top: 0;
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            text-align: center;
            margin-bottom: 24px;
        }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: var(--border-color);
            z-index: 1;
        }
        .step {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--card-bg);
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            z-index: 2;
            color: var(--text-muted);
        }
        .step.active {
            border-color: var(--primary);
            color: #fff;
            background-color: var(--primary);
        }
        .step.completed {
            border-color: var(--success);
            color: #fff;
            background-color: var(--success);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
            color: var(--text-color);
        }
        input, select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-color);
            color: #fff;
            box-sizing: border-box;
            font-size: 14px;
        }
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn {
            background-color: var(--primary);
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            font-size: 15px;
            transition: background-color 0.2s;
        }
        .btn:hover {
            background-color: var(--primary-hover);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.15);
            border: 1px solid var(--danger);
            color: #fca5a5;
        }
        .alert-success {
            background-color: rgba(34, 197, 94, 0.15);
            border: 1px solid var(--success);
            color: #86efac;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        td, th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }
        .badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background-color: rgba(34, 197, 94, 0.2);
            color: var(--success);
        }
        .badge-danger {
            background-color: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h2>Εγκατάσταση AppForm</h2>
            
            <div class="steps">
                <div class="step <?= $step >= 1 ? 'completed' : ($step == 1 ? 'active' : '') ?>">1</div>
                <div class="step <?= $step >= 2 ? 'completed' : ($step == 2 ? 'active' : '') ?>">2</div>
                <div class="step <?= $step >= 3 ? 'completed' : ($step == 3 ? 'active' : '') ?>">3</div>
                <div class="step <?= $step >= 4 ? 'completed' : ($step == 4 ? 'active' : '') ?>">4</div>
                <div class="step <?= $step >= 5 ? 'completed' : ($step == 5 ? 'active' : '') ?>">5</div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </div>
</body>
</html>
