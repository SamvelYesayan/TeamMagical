<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/** Whitelist-based upload validation: extension, size, and real image content for images. */
class WorkFile implements ValidationRule
{
    public function __construct(private readonly bool $imageOnly = false)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Ֆայլը վավեր չէ կամ չի բեռնվել։');

            return;
        }

        $ext = strtolower($value->getClientOriginalExtension());
        $images = config('magical.uploads.image_extensions');
        $allowed = $this->imageOnly ? $images : config('magical.uploads.extensions');

        if (! in_array($ext, $allowed, true)) {
            $fail($this->imageOnly
                ? 'Թույլատրվում են միայն նկարներ (JPG, PNG, WEBP, GIF)։'
                : "«{$value->getClientOriginalName()}»՝ ֆայլի տեսակը թույլատրված չէ։");

            return;
        }

        $size = $value->getSize();
        if ($size === false || $size === 0) {
            $fail("«{$value->getClientOriginalName()}»՝ ֆայլը դատարկ է։");

            return;
        }

        $maxKb = $this->imageOnly ? 8 * 1024 : config('magical.uploads.max_kb');
        if ($size > $maxKb * 1024) {
            $fail("«{$value->getClientOriginalName()}»՝ ֆայլը մեծ է թույլատրելի չափից (".round($maxKb / 1024).' ՄԲ)։');

            return;
        }

        if (in_array($ext, $images, true) && @getimagesize($value->getRealPath()) === false) {
            $fail("«{$value->getClientOriginalName()}»՝ նկարի բովանդակությունը վավեր չէ։");
        }
    }
}
