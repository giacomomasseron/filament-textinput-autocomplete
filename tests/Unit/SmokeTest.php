<?php

it('boots the package service provider', function () {
    expect(class_exists(\Giacomo\TextInputAutocomplete\TextInputAutocompleteServiceProvider::class))->toBeTrue();
});
