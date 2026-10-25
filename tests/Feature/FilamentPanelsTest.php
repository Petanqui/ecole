<?php

dataset('panels', ['gestion', 'portail']);

test('the panel login page is reachable', function (string $panel) {
    $this->get("/{$panel}/login")->assertOk();
})->with('panels');

test('guests are redirected from the panel dashboard to its login page', function (string $panel) {
    $this->get("/{$panel}")->assertRedirect("/{$panel}/login");
})->with('panels');
