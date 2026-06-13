<?php

it('boots the package service provider', function () {
    expect(class_exists(\GiacomoMasseroni\TextInputAutocomplete\TextInputAutocompleteServiceProvider::class))->toBeTrue();
});
