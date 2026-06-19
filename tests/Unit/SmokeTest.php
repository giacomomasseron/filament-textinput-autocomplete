<?php

use GiacomoMasseroni\TextInputAutocomplete\TextInputAutocompleteServiceProvider;

it('boots the package service provider', function () {
    expect(class_exists(TextInputAutocompleteServiceProvider::class))->toBeTrue();
});
