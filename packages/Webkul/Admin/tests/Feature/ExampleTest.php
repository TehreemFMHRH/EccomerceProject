<?php

test('the admin login page returns a successful response', function () {
    $resp = $this->get('/admin/login');

    $resp->assertStatus(200);
});
