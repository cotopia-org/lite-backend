<?php

namespace App\Utilities;

use App\Models\Setting;

trait Codeable {


    protected function getArrayableAppends() {
        $this->appends = array_unique(array_merge($this->appends, ['code']));

        return parent::getArrayableAppends();
    }

    public function getCodeAttribute($value): string {
        return base_convert(10000000 - $this->id, 10, 36);

    }

    public static function findByCode($value) {
        return self::findOrFail(10000000 - base_convert($value, 36, 10));

    }

}
