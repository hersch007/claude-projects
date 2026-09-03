<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/parsedown.php';
require_login();

$slug   = $_GET['slug'] ?? '';
$lesson = get_lesson($slug);

if (!$lesson) {
    header('Location: /wts_documentation/training/dashboard.php');
    exit;
}

$user        = current_user();
$lessons     = get_lessons($lesson['course_id']);
$is_complete = is_lesson_complete($user['id'], $lesson['id']);
$quiz_result = get_quiz_result($user['id'], $lesson['id']);

// Find prev/next
$prev = $next = null;
foreach ($lessons as $i => $l) {
    if ($l['id'] === $lesson['id']) {
        $prev = $lessons[$i - 1] ?? null;
        $next = $lessons[$i + 1] ?? null;
        break;
    }
}

// Load markdown content
$content_path = __DIR__ . '/content/' . $lesson['content_file'];
$raw_content  = file_exists($content_path) ? file_get_contents($content_path) : "# {$lesson['title']}\n\nContent coming soon.";

// Parse markdown
$pd      = new Parsedown();
$pd->setSafeMode(true);
$content = $pd->text($raw_content);

// Handle quiz submission
$quiz_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_verify();

// Retrieve last answers from session after redirect, then clear
$last_answers = $_SESSION['last_quiz_answers'][$lesson['id']] ?? [];
if (!empty($last_answers)) unset($_SESSION['last_quiz_answers'][$lesson['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quiz_submit'])) {
    $quiz_file = __DIR__ . '/content/' . str_replace('.md', '-quiz.json', $lesson['content_file']);
    if (file_exists($quiz_file)) {
        $quiz_data = json_decode(file_get_contents($quiz_file), true);
        $score = 0;
        $answers = [];
        foreach ($quiz_data['questions'] as $qi => $q) {
            $answer = $_POST['q' . $qi] ?? '';
            $answers[$qi] = $answer;
            if ($answer === $q['answer']) $score++;
        }
        save_quiz_result($user['id'], $lesson['id'], $score, count($quiz_data['questions']));
        $_SESSION['last_quiz_answers'][$lesson['id']] = $answers;
        header('Location: /wts_documentation/training/lesson.php?slug=' . urlencode($slug) . '#quiz-section');
        exit;
    }
}

// Mark complete without quiz (lessons with no quiz file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    mark_lesson_complete($user['id'], $lesson['id']);
    $is_complete = true;
    header('Location: /wts_documentation/training/lesson.php?slug=' . urlencode($slug) . '&done=1');
    exit;
}

// Unmark complete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_incomplete'])) {
    $stmt = db()->prepare('DELETE FROM progress WHERE user_id = ? AND lesson_id = ?');
    $stmt->execute([$user['id'], $lesson['id']]);
    $stmt2 = db()->prepare('DELETE FROM quiz_results WHERE user_id = ? AND lesson_id = ?');
    $stmt2->execute([$user['id'], $lesson['id']]);
    header('Location: /wts_documentation/training/lesson.php?slug=' . urlencode($slug));
    exit;
}

$quiz_file = __DIR__ . '/content/' . str_replace('.md', '-quiz.json', $lesson['content_file']);
$has_quiz  = file_exists($quiz_file);
$quiz_data = $has_quiz ? json_decode(file_get_contents($quiz_file), true) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($lesson['title']) ?> &mdash; WTS AMP Training</title>
<link rel="stylesheet" href="/wts_documentation/training/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="logo">
        <img src="/wts_documentation/training/assets/img/wts-logo.png" alt="WTS">
        <span>AMP Training Portal</span>
    </div>
    <nav>
        <a href="/wts_documentation/training/course.php?slug=<?= urlencode($lesson['course_slug']) ?>">&larr; <?= htmlspecialchars($lesson['course_title']) ?></a>
        <a href="/wts_documentation/training/logout.php">Sign Out</a>
    </nav>
</header>

<div class="page-wrap">
    <div class="lesson-layout">

        <!-- Sidebar nav -->
        <aside class="lesson-nav card">
            <h4><?= htmlspecialchars($lesson['course_title']) ?></h4>
            <ul>
                <?php foreach ($lessons as $l):
                    $done = is_lesson_complete($user['id'], $l['id']);
                    $cls  = ($l['id'] === $lesson['id'] ? 'active ' : '') . ($done ? 'done' : 'not-done');
                ?>
                <li>
                    <a href="/wts_documentation/training/lesson.php?slug=<?= urlencode($l['slug']) ?>" class="<?= $cls ?>">
                        <?= htmlspecialchars($l['title']) ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Main content -->
        <main>
            <?php if (isset($_GET['done'])): ?>
                <div class="alert alert-success">Lesson marked complete!</div>
            <?php endif; ?>

            <?php
            // Reusable mark incomplete form
            $incomplete_form = $is_complete ? '
                <form method="POST" style="margin:0">
                    <input type="hidden" name="csrf_token" value="' . csrf_token() . '">
                    <button type="submit" name="mark_incomplete" class="btn btn-sm btn-danger"
                            onclick="return confirm(\'Remove completion and clear quiz for this lesson?\')">
                        Mark Incomplete
                    </button>
                </form>' : '';
            ?>

            <?php if ($is_complete): ?>
                <div class="alert alert-success" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
                    <span>&#10003; You have completed this lesson.</span>
                    <?= $incomplete_form ?>
                </div>
            <?php endif; ?>

            <div class="card lesson-content">
                <?= $content ?>
            </div>

            <!-- Quiz -->
            <?php if ($has_quiz && $quiz_data): ?>
            <div class="card quiz-section" id="quiz-section">
                <h2 style="color:var(--navy);margin-bottom:1rem">Checkpoint Quiz</h2>

                <?php if ($quiz_result): ?>
                    <div class="quiz-result <?= $quiz_result['passed'] ? 'pass' : 'fail' ?>" style="margin-bottom:1.25rem">
                        <?php if ($quiz_result['passed']): ?>
                            &#10003; Passed &mdash; <?= $quiz_result['score'] ?>/<?= $quiz_result['total'] ?> correct. This lesson is now complete.
                        <?php else: ?>
                            &#10007; Not yet passing &mdash; <?= $quiz_result['score'] ?>/<?= $quiz_result['total'] ?> correct. You need 80% or higher to complete this lesson.
                        <?php endif; ?>
                    </div>

                    <?php foreach ($quiz_data['questions'] as $qi => $q):
                        $submitted = $last_answers[$qi] ?? null;
                        $correct   = $q['answer'];
                        $answered_correctly = $submitted === $correct;
                    ?>
                    <div style="margin-bottom:1.75rem">
                        <p class="quiz-question"><?= ($qi+1) ?>. <?= htmlspecialchars($q['question']) ?></p>
                        <?php foreach ($q['options'] as $key => $label): ?>
                        <div style="display:flex;align-items:flex-start;gap:.6rem;margin-bottom:.4rem;padding:.4rem .6rem;border-radius:5px;
                            <?php if ($key === $correct): ?>background:#def7ec;border:1px solid #84e1bc;
                            <?php elseif ($submitted === $key && !$answered_correctly): ?>background:#fde8e8;border:1px solid #f8b4b4;
                            <?php else: ?>border:1px solid transparent;<?php endif; ?>">
                            <span style="font-weight:700;min-width:20px"><?= h($key) ?>)</span>
                            <span><?= h($label) ?></span>
                            <?php if ($key === $correct): ?>
                                <span style="margin-left:auto;color:var(--green);font-weight:700">&#10003; Correct</span>
                            <?php elseif ($submitted === $key && !$answered_correctly): ?>
                                <span style="margin-left:auto;color:var(--red);font-weight:700">&#10007; Your answer</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if (isset($q['explanation']) && $submitted !== null): ?>
                        <div style="margin-top:.75rem;padding:.6rem .9rem;background:#e9f7fd;border-left:4px solid var(--cyan);border-radius:0 5px 5px 0;font-size:.9rem">
                            <strong>Why <?= h($correct) ?> is correct:</strong> <?= h($q['explanation']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <?php if (!$quiz_result['passed']): ?>
                    <p style="margin-top:1rem;font-size:.9rem;color:#666">Review the lesson above, then click <strong>Try Again</strong> when ready.</p>
                    <form method="POST" style="margin-top:.5rem">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <button type="submit" name="mark_incomplete" class="btn btn-navy btn-sm">&#8634; Try Again</button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!$quiz_result): ?>
                <form method="POST" id="quiz-form">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <?php foreach ($quiz_data['questions'] as $qi => $q): ?>
                    <div style="margin-bottom:1.5rem">
                        <p class="quiz-question"><?= ($qi+1) ?>. <?= htmlspecialchars($q['question']) ?></p>
                        <?php foreach ($q['options'] as $key => $label): ?>
                        <label class="quiz-option">
                            <input type="radio" name="q<?= $qi ?>" value="<?= htmlspecialchars($key) ?>" required>
                            <span><?= htmlspecialchars($label) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>
                    <button type="submit" name="quiz_submit" class="btn btn-primary">Submit Quiz</button>
                </form>
                <?php endif; ?>
            </div>

            <?php elseif (!$is_complete): ?>
            <!-- No quiz - simple complete button -->
            <form method="POST" style="margin-bottom:1.5rem">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" name="mark_complete" class="btn btn-success">
                    &#10003; Mark Lesson Complete
                </button>
            </form>
            <?php endif; ?>

            <!-- Bottom status + Mark Incomplete -->
            <?php if ($is_complete): ?>
                <div class="alert alert-success" style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem">
                    <span>&#10003; Lesson complete.</span>
                    <?= $incomplete_form ?>
                </div>
            <?php endif; ?>

            <!-- Prev / Next navigation -->
            <div style="display:flex;justify-content:space-between;gap:1rem;margin-top:1rem">
                <?php if ($prev): ?>
                    <a href="/wts_documentation/training/lesson.php?slug=<?= urlencode($prev['slug']) ?>" class="btn btn-navy">&larr; Previous Lesson</a>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>

                <?php if ($next): ?>
                    <a href="/wts_documentation/training/lesson.php?slug=<?= urlencode($next['slug']) ?>" class="btn btn-primary">Next Lesson &rarr;</a>
                <?php else: ?>
                    <a href="/wts_documentation/training/course.php?slug=<?= urlencode($lesson['course_slug']) ?>" class="btn btn-success">&larr; Back to Course</a>
                <?php endif; ?>
            </div>
        </main>

    </div>
</div>

</body>
</html>
