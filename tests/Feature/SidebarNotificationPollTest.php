<?php

test('home page redirects guests to login', function () {
    $this->get('/')
        ->assertRedirect(route('login'));
});
