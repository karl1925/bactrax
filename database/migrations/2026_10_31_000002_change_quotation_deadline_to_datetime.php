<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('procurements', 'quotation_deadline')) {
            Schema::table('procurements', function ($table) {
                $table->dateTime('quotation_deadline')->nullable()->change();
            });
        }

        $this->updateRfqNotice(true);
    }

    public function down(): void
    {
        if (Schema::hasColumn('procurements', 'quotation_deadline')) {
            Schema::table('procurements', function ($table) {
                $table->date('quotation_deadline')->nullable()->change();
            });
        }

        $this->updateRfqNotice(false);
    }

    private function updateRfqNotice(bool $useDateTime): void
    {
        $notice = DB::table('settings')->where('key', 'rfq_notice')->first(['id', 'value']);
        if (! $notice || ! is_string($notice->value)) {
            return;
        }

        $value = preg_replace_callback(
            '/<[^>]*\bdata-input=(["\'])procurement\.quotation_deadline\1[^>]*>/i',
            static function (array $matches) use ($useDateTime): string {
                $element = preg_replace_callback(
                    '/\bdata-input-type=(["\'])(.*?)\1/i',
                    static fn (array $attribute): string => 'data-input-type='.$attribute[1].($useDateTime ? 'datetime-local' : 'date').$attribute[1],
                    $matches[0],
                );

                return preg_replace_callback(
                    '/\bdata-format=(["\'])(.*?)\1/i',
                    static fn (array $attribute): string => 'data-format='.$attribute[1].($useDateTime ? 'date:MMMM d, yyyy, hh:mm a' : 'date:MMMM d, yyyy').$attribute[1],
                    $element,
                );
            },
            $notice->value,
        );

        if ($value !== null && $value !== $notice->value) {
            DB::table('settings')->where('id', $notice->id)->update([
                'value' => $value,
                'updated_at' => now(),
            ]);
        }
    }
};
