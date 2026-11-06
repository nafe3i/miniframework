<?php
/**
 * Error page used by the ExceptionHandler.
 *
 * @var int $status
 * @var string $title
 * @var string $message Client-safe message (details only in debug mode)
 */
$this->layout('layouts/app', ['title' => $status . ' ' . $title]);
?>
<section class="card error-page">
    <p class="error-page__status"><?= $this->e($status) ?></p>
    <h1><?= $this->e($title) ?></h1>
    <pre class="error-page__message"><?= $this->e($message) ?></pre>
    <p><a class="button" href="/tasks">Back to tasks</a></p>
</section>
