<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class FormContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::defaults() as $key => $value) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'group' => 'form_cms',
                    'description' => match ($key) {
                        'forms_header' => 'Shared header HTML for forms.',
                        'rfq_notice' => 'RFQ notice HTML.',
                        'rfq_instructions' => 'RFQ instructions HTML.',
                        default => 'RFQ terms and conditions HTML.',
                    },
                ],
            );
        }
    }

    public static function defaults(): array
    {
        return [
            'forms_header' => <<<'HTML'
<img src="/resources/header.png" alt="Department of Information and Communications Technology" style="display:block;width:314px;height:65px;max-width:100%;object-fit:contain;margin:0 auto">
<p style="margin:5px 0;line-height:1.2;text-align:center"><strong>REGIONAL OFFICE 02</strong><br>02 Bagay Road, San Gabriel Village, Tuguegarao City, Cagayan 3500</p>
HTML,
            'rfq_notice' => <<<'HTML'
<p style="margin:14px 0 8px;font-weight:700;line-height:1.2">NOTICE TO ALL SERVICE PROVIDERS AND SUPPLIERS:</p>
<p style="line-height:1.2">Please quote your lowest price on the item/s listed below, subject to the conditions stated below, stating the shortest time of delivery and submit your quotation duly signed by you or your authorized representative not later than <strong data-input="procurement.quotation_deadline" data-input-type="datetime-local" data-format="date:MMMM d, yyyy, hh:mm a" data-required data-label="Quotation Submission Deadline"></strong>, duly sealed in an envelope addressed to:</p>
<div style="margin:15px 0;padding-left:54px">
    <strong data-print="rfq.bac_secretariat_name">RONALD S. BARIUAN</strong>
    <br>Head, BAC Secretariat
    <br>DICT - Region 02, Tuguegarao City, Cagayan
    <br>Email Address: <span data-print="rfq.contact_email">bac.r2@dict.gov.ph</span>
</div>
HTML,
            'rfq_instructions' => <<<'HTML'
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
HTML,
            'rfq_terms' => <<<'HTML'
<div data-page-break-before="always" style="display:flex;width:100%;min-height:20px;align-items:center;justify-content:center;background-color:rgb(255,248,208);font-family:Arial,Helvetica,sans-serif;font-weight:800;text-align:center">TERMS AND CONDITION</div>
<ol style="margin:8px 0 12px -20px;font-size:11.5px">
    <li style="margin-bottom:3px">Price quotation/s, to be denominated in Philippine peso, shall include all taxes, duties, and/or levies payable.</li>
    <li style="margin-bottom:3px">Bids should be valid for at least 60 calendar days from the deadline of submission.</li>
    <li style="margin-bottom:3px">Service providers or suppliers shall provide correct and accurate technical specifications, brand name, and product model in this form.</li>
    <li style="margin-bottom:3px">Quotations exceeding the Approved Budget for the Contract shall be rejected.</li>
    <li style="margin-bottom:3px">Evaluation will be on a LOT basis.</li>
    <li style="margin-bottom:3px">Award of the contract shall be made to the lowest calculated and responsive quotation which complies with the minimum technical specifications and other terms and conditions stated herein.</li>
    <li style="margin-bottom:3px">Any interlineations, erasures, or overwriting shall be valid only if signed or initialed by you or your duly authorized representative/s.</li>
    <li style="margin-bottom:3px">The item/s shall be delivered according to the requirements specified in the Technical Specifications or Terms of Reference.</li>
    <li style="margin-bottom:3px">DICT shall have the right to reject any or all offers and accept an offer considered most advantageous to the Agency.</li>
    <li style="margin-bottom:3px">If two or more bidders submit the Lowest Calculated Quotation/Lowest Calculated and Responsive Quotation, DICT shall employ “draw lots” as the tie-breaking method under GPPB Circular 06-2005.</li>
    <li style="margin-bottom:3px">A penalty of one-tenth (1/10) of one percent shall be imposed for every day of delay. DICT may rescind the contract when cumulative liquidated damages reach ten percent (10%) of the contract amount.</li>
    <li style="margin-bottom:3px">Terms of Payment: Payment shall be processed within 15 to 30 working days after inspection and acceptance, and submission of required supporting documents, under existing government accounting rules. Any bank transfer fee shall be chargeable to the bidder.</li>
</ol>
HTML,
        ];
    }
}
