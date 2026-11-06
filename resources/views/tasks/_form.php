<?php
/**
 * Task form fields, shared by the create and edit pages.
 *
 * @var string $action Form target URL
 * @var string $submitLabel
 * @var array<string, string> $old Field values
 * @var array<string, list<string>> $errors Validation errors per field
 * @var list<App\Entities\TaskStatus> $statuses
 */
$fieldError = function (string $field) use ($errors): string {
    if (!isset($errors[$field])) {
        return '';
    }

    return '<p class="field__error" id="' . $field . '-error">' . $this->e(implode(' ', $errors[$field])) . '</p>';
};
$invalid = static fn (string $field): string => isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . $field . '-error"' : '';
?>
<?php if ($errors !== []): ?>
    <div class="alert alert--error" role="alert">Please fix the errors below.</div>
<?php endif; ?>

<form class="card form" method="post" action="<?= $this->e($action) ?>" novalidate>
    <div class="field">
        <label for="title">Title <span class="required">*</span></label>
        <input id="title" name="title" type="text" maxlength="255" required
               value="<?= $this->e($old['title'] ?? '') ?>"<?= $invalid('title') ?>>
        <?= $fieldError('title') ?>
    </div>

    <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="5" maxlength="2000"<?= $invalid('description') ?>><?= $this->e($old['description'] ?? '') ?></textarea>
        <?= $fieldError('description') ?>
    </div>

    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status"<?= $invalid('status') ?>>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= $this->e($status->value) ?>"<?= ($old['status'] ?? '') === $status->value ? ' selected' : '' ?>><?= $this->e($status->label()) ?></option>
            <?php endforeach; ?>
        </select>
        <?= $fieldError('status') ?>
    </div>

    <div class="form__actions">
        <button class="button" type="submit"><?= $this->e($submitLabel) ?></button>
        <a class="button button--ghost" href="/tasks">Cancel</a>
    </div>
</form>
