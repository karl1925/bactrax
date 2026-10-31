<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $legacyInstructions = DB::table('settings')->where('key', 'rfq_instructions')->value('value');
        if ($legacyInstructions === null) return;

        $legacyNotice = str_contains($legacyInstructions, 'NOTICE TO ALL SERVICE PROVIDERS AND SUPPLIERS:')
            ? $legacyInstructions
            : <<<'HTML'
<p style="margin:14px 0 8px;font-weight:700;line-height:1.2">NOTICE TO ALL SERVICE PROVIDERS AND SUPPLIERS:</p>
<p style="line-height:1.2">Please quote your lowest price on the item/s listed below, subject to the conditions stated below, stating the shortest time of delivery and submit your quotation duly signed by you or your authorized representative not later than <strong data-input="procurement.quotation_deadline" data-input-type="datetime-local" data-format="date:MMMM d, yyyy, hh:mm a" data-required data-label="Quotation Submission Deadline"></strong>, duly sealed in an envelope addressed to:</p>
<div style="margin:15px 0;padding-left:54px">
    <strong data-print="rfq.bac_secretariat_name">RONALD S. BARIUAN</strong>
    <br>Head, BAC Secretariat
    <br>DICT - Region 02, Tuguegarao City, Cagayan
    <br>Email Address: <span data-print="rfq.contact_email">bac.r2@dict.gov.ph</span>
</div>
HTML;

        DB::table('settings')->updateOrInsert(
            ['key' => 'rfq_notice'],
            ['value' => $legacyNotice, 'group' => 'form_cms', 'description' => 'RFQ notice HTML.', 'updated_at' => now(), 'created_at' => now()],
        );

        if (str_contains($legacyInstructions, 'NOTICE TO ALL SERVICE PROVIDERS AND SUPPLIERS:')) {
            $instructions = <<<'HTML'
<p style="margin:5px 2px;font-weight:700;line-height:1.0">INSTRUCTIONS:</p>
<ol style="margin-left:-20px;font-size:11.0px">
    <li style="margin-bottom:3px">The bidder shall provide correct and accurate information in this form.</li>
    <li style="margin-bottom:3px">The bidder shall submit this form duly accomplished and signed by the company's authorized representative.</li>
    <li style="margin-bottom:3px">Do not alter the contents of this form in any way.</li>
    <li style="margin-bottom:3px">All technical specifications are mandatory. Failure to comply with any mandatory requirements will disqualify your quotation.</li>
    <li style="margin-bottom:3px">Bidders must state their compliance in the “Statement of Compliance” against each individual parameter of each specification in the Item Description or attachments, if any.</li>
    <li style="margin-bottom:3px">Please do not leave any blank items. Instead, indicate “NONE” if the item is not available.</li>
    <li style="margin-bottom:3px">Failure to follow instructions will disqualify your entire quotation.</li>
    <li style="margin-bottom:3px">Quotation and other required documents may be sent electronically as an advance copy to meet the deadline. The original copy should still be forwarded to the office.</li>
    <li style="margin-bottom:3px">Submit the latest Income/Business Tax Return for ABCs above Php 500,000.00 upon receipt of the Purchase Order.</li>
    <li style="margin-bottom:3px">Submit the Omnibus Sworn Statement using the GPPB-prescribed format for ABCs above Php 50,000.00 upon receipt of the Purchase Order.</li>
    <li style="margin-bottom:3px">To ensure the authenticity of your submission, sign every page of this document.</li>
    <li style="margin-bottom:3px">Bidders must fill out the “Unit Value” for each item. Leaving any unit price blank will result in disqualification.</li>
    <li style="margin-bottom:3px">Bidders must indicate the LandBank account name and account number in the designated portion of the RFQ.</li>
</ol>
HTML;
            DB::table('settings')->where('key', 'rfq_instructions')->update([
                'value' => $instructions,
                'description' => 'RFQ instructions HTML.',
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $notice = DB::table('settings')->where('key', 'rfq_notice')->value('value');
        if ($notice !== null) {
            DB::table('settings')->updateOrInsert(
                ['key' => 'rfq_instructions'],
                ['value' => $notice, 'group' => 'form_cms', 'description' => 'RFQ instructions HTML.', 'updated_at' => now(), 'created_at' => now()],
            );
        }
        DB::table('settings')->where('key', 'rfq_notice')->delete();
    }
};
