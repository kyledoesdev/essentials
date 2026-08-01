<?php

use Illuminate\Database\Eloquent\Model;
use Kyledoesdev\Essentials\Concerns\HasStatsAfterEvents;

describe('HasStatsAfterEvents', function () {
    it('registers created and deleted listeners when a model uses the trait', function () {
        $model = new class extends Model
        {
            use HasStatsAfterEvents;
        };

        $dispatcher = $model::getEventDispatcher();

        expect($dispatcher->hasListeners('eloquent.created: '.get_class($model)))->toBeTrue()
            ->and($dispatcher->hasListeners('eloquent.deleted: '.get_class($model)))->toBeTrue();
    });
});
