<?php
/** @var string $appName */
$this->layout('layouts/app', ['title' => 'Home']);
?>
<section class="hero">
    <h1><?= $this->e($appName) ?></h1>
    <p class="lead">
        This page was routed by the MiniForge Router, dispatched to
        <code>HomeController::index</code> through the Container, and rendered
        by the PHP View Renderer inside a layout.
    </p>
</section>
