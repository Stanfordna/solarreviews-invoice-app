<?php

namespace Tests\Feature;

use App\Http\Resources\InvoiceResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\Address;
use App\Models\Invoice;
use App\Models\LineItem;
use Tests\TestCase;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertGreaterThan;

class InvoiceCrudTest extends TestCase
{
    use RefreshDatabase;
    protected $seed = true;
    protected int $totalInvoiceRecords = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totalInvoiceRecords = Invoice::count();
    }

    /**
     * Generate a well-formed request for POST/PUT/PATCH operations.
     * Function call can specify id as well as status.
     * For tests of behaviors when missing data, test methods should manually set values to "" or null.
     * 
     * TODO: when objects are specified populate with that data
     */
    protected function createInvoicesRequestData(
        string $id = '',
        ?string $status = null,
        ?Invoice $invoice = null,
        ?Client $client = null,
        ?Address $clientAddress = null,
        ?Address $senderAddress = null
        ): array {

        if (is_null($invoice)) {
            if (is_null($status)) {
                $status = 'draft';
            }
            $data = [
                'id' => $id,
                'issue_date' => date('Y-m-d'),
                'description' => 'Test invoice description',
                'payment_terms' => 30,
                'client_name' => 'Ricky Bobby',
                'client_email' => 'el.diablo@gmail.com',
                'status' => $status,
                'sender_address' => [
                    'street' => '930 Acoma Street Unit 316',
                    'city' => 'Denver',
                    'postal_code' => '80204',
                    'country' => 'United States of America',
                ],
                'client_address' => [
                    'street' => '17240 Connor Quay Ct',
                    'city' => 'Cornelius',
                    'postal_code' => '28031',
                    'country' => 'United States of America',
                ],
                'line_items' => [
                    [
                        'name' => 'apple',
                        'quantity' => 1,
                        'price_unit_cents' => 5000
                    ],
                    [
                        'name' => 'banana',
                        'quantity' => 3,
                        'price_unit_cents' => 4000
                    ],
                    [
                        'name' => 'carrot',
                        'quantity' => 2,
                        'price_unit_cents' => 2500
                    ],
                ],
            ];
        } else {
            $data = new InvoiceResource($invoice)->toArray(request());
            if (isset($status)) {
                $data['status'] = $status;
            }
        }

        if (isset($client)) {
            $data['client_name'] = $client->full_name;
            $data['client_email'] = $client->email;
        }
        if (isset($clientAddress)) {
            $data['sender_address']['street'] = $senderAddress->street;
            $data['sender_address']['city'] = $senderAddress->city;
            $data['sender_address']['postal_code'] = $senderAddress->postal_code;
            $data['sender_address']['country'] = $senderAddress->country;
        }
        if (isset($senderAddress)) {
            $data['client_address']['street'] = $clientAddress->street;
            $data['client_address']['city'] = $clientAddress->city;
            $data['client_address']['postal_code'] = $clientAddress->postal_code;
            $data['client_address']['country'] = $clientAddress->country;
        }

        return $data;
    }

    /*************************************************************************************
     ******************************** Common Tests ***************************************
     *************************************************************************************
     * Purpose:
     * - functions for repetitive assertions that many responses and records should adhere to
     *
     * Assertions
     * - ensure database contains a specific invoice id and that it is well formed
     * - ensure database has line items with specific invoice id and validate the total (compare db total to calculated request line items sum)
     * - validate that a record is the first/oldest in its table
     * - validate that a record is the last/latest record in its table
     */
    public function validate_invoice_id($invoiceId) {
        // database contains a specific invoice id and that it matches the appropriate format
        $this->assertDatabaseHas('invoices', ['id' => $invoiceId]);
        $this->assertMatchesRegularExpression('/^[A-Z]{2}[0-9]{4}$/', $invoiceId);
    }

    public function validate_invoice_line_items_and_total($invoiceId, $requestLineItems) {
        $this->assertEquals(LineItem::where('invoice_id', $invoiceId)->count(), count($requestLineItems));

        // Make a map of each line item to its name, make sure line items are unique within an invoice
        $comparisonLineItems = [];
        foreach($requestLineItems as $lineItem) {
            $this->assertArrayNotHasKey($lineItem['name'], $comparisonLineItems, "Invalid request. Line items on an invoice should have unique names.");
            $comparisonLineItems["{$lineItem['name']}"] = $lineItem;
        }

        // make sure database line items exactly match request line items using the map
        $dbLineItems = LineItem::where('invoice_id', $invoiceId)->get();
        $total = 0; // keep running total for validation that database has accurate total
        foreach ($dbLineItems as $dbLineItem) {
            $this::assertArrayHasKey($dbLineItem['name'], $comparisonLineItems,
                "Validation failed. {$dbLineItem['name']} is in line_items table but not in request line items. Run this test after any POST/PUT/PATCH operation.");
            $this->assertEquals($dbLineItem['quantity'], $comparisonLineItems[$dbLineItem['name']]['quantity']);
            $this->assertEquals($dbLineItem['price_unit_cents'], $comparisonLineItems[$dbLineItem['name']]['price_unit_cents']);
            $total += $dbLineItem['quantity'] * $dbLineItem['price_unit_cents'];
            unset($comparisonLineItems[$dbLineItem['name']]);
        }
        $this->assertCount(0, $comparisonLineItems);
        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'total_cents' => $total]);
    }

    /**
     * The assumption here is that "oldest" and "lowest primary key" mean the same thing.
     * Specifically for this app, we alow duplicates in clients and addresses so long as
     * all pending and paid invoices of the same client refer to the original.
     */
    public function validate_record_is_latest_copy_in_table($record) {
        $modelClassName = get_class($record);

        // Get all the model's attributes as an array
        $attributes = $record->getAttributes();

        unset($attributes['id']);
        unset($attributes['created_at']);
        unset($attributes['updated_at']);

        // Get the first occurrence
        $matchRecord = $modelClassName::where($attributes)->orderby('id', 'desc')->first();
        $this->assertEquals($record->id, $matchRecord->id);
    }

    /**
     * The use case for this function is:
     * "If I just made a POST request or a PUT request and the user or address is new, was a new record created?"
     */
    public function validate_record_is_oldest_copy_in_table($record) {
        $modelClassName = get_class($record);

        // Get all the model's attributes as an array
        $attributes = $record->getAttributes();

        unset($attributes['id']);
        unset($attributes['created_at']);
        unset($attributes['updated_at']);

        // Get the first occurrence
        $matchRecord = $modelClassName::where($attributes)->orderby('id', 'asc')->first();
        $this->assertEquals($record->id, $matchRecord->id);

    }

    /*************************************************************************************
     *************** Test: GET /invoices (InvoiceController::index()) ********************   TODO: Make sure this is finished
     *************************************************************************************
     * Purpose:
     * - Verify the /api/invoices endpoint returns a collection of invoices
     *   and includes expected top-level meta (count, api_version) when using the
     *   InvoicesResourceCollection.
     *
     * Assertions:
     * - HTTP 200
     * - Each invoice object matches InvoiceResource shape (id, issue_date, client_name, line_items, total_cents, etc.)
     * - JSON has "data" array with same number of objects as Invoice records in the database.
     * - JSON meta contains 'count' equal to the expected count of invoices.
     * - If no invoices exist expect empty data array and meta count 0
     */

    public function test_invoices_index_returns_expected_json_structure_and_code_200(): void {
        $response = $this->get(route('invoices.index'));
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'issue_date',
                        'due_date',
                        'description',
                        'payment_terms',
                        'client_name',
                        'client_email',
                        'status',
                        'sender_address' => [
                            'street',
                            'city',
                            'postal_code',
                            'country'
                        ],
                        'client_address' => [
                            'street',
                            'city',
                            'postal_code',
                            'country'
                        ],
                        'line_items' => [
                            '*' => [
                                'id',
                                'name',
                                'quantity',
                                'price_unit_cents',
                                'price_total_cents'
                            ],
                        ],
                        'total_cents'
                    ],
                ],
                'meta' => [
                    'count',
                    'api_version'
                ]
            ]);
    }

    // uses protected int $totalInvoiceRecords assigned in setUp()
    public function test_invoices_index_returns_appropriate_number_of_invoices_with_count_in_metadata(): void {
        $response = $this->get(route('invoices.index'));
        $response
            ->assertStatus(200)
            ->assertJsonCount($this->totalInvoiceRecords, 'data')
            ->assertJson([
                'meta' => [
                    'count' => $this->totalInvoiceRecords
                ],
            ]);
    }

    public function test_invoices_index_behaves_even_if_there_are_no_invoices(): void {
        // empty table to test edge case of empty database
        Invoice::truncate();
        $response = $this->get(route('invoices.index'));
        $response
            ->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'meta' => [
                    'count' => 0
                ],
            ]);
    }


    /*************************************************************************************
     ******************** Test: POST /invoices (InvoiceController::store()) **************
     *************************************************************************************
     * Purpose:
     * - Verify creating an invoice via the API persists a new invoice and related
     *   objects follow the expected behavior (client/addresses created or attached).
     *
     * Assertions:
     * - Appropriate HTTP status (201 Created)
     * - Response JSON contains invoice id and a row with that id is in the database
        * - Database contains the created invoice row with expected values
        * - Related client/address rows exist (if controller creates them)
        * - A well-formed id is created for the invoice (2 Uppercase Letters and 4 Numbers)
     * - Missing required fields -> expect validation error (422)
     *
     */
    public function test_post_draft_always_creates_client_and_addresses(): void {
        // assert that even if we use data of an existing client and addresses in an invoice,
        // new client and address records are created.
        $randomClient = Client::inRandomOrder()->first();
        $randomSenderAddress = Address::inRandomOrder()->first();
        // make sure addresses are different for when we use validate_record_is_latest_copy_in_table()
        $randomClientAddress = Address::inRandomOrder()->where('id', '!=', $randomSenderAddress->id)->first();

        $draftInvoiceData = $this->createInvoicesRequestData(); // populate a draft
        // populate client and addresses with data that matches data already in the database
        $draftInvoiceData['client_name'] = $randomClient->full_name;
        $draftInvoiceData['client_email'] = $randomClient->email;
        $draftInvoiceData['sender_address']['street'] = $randomSenderAddress->street;
        $draftInvoiceData['sender_address']['city'] = $randomSenderAddress->city;
        $draftInvoiceData['sender_address']['postal_code'] = $randomSenderAddress->postal_code;
        $draftInvoiceData['sender_address']['country'] = $randomSenderAddress->country;
        $draftInvoiceData['client_address']['street'] = $randomClientAddress->street;
        $draftInvoiceData['client_address']['city'] = $randomClientAddress->city;
        $draftInvoiceData['client_address']['postal_code'] = $randomClientAddress->postal_code;
        $draftInvoiceData['client_address']['country'] = $randomClientAddress->country;
        $response = $this->post(route('invoices.store'), $draftInvoiceData);
        $response->assertStatus(201); 
        $invoiceId = $response->json('invoice_id');
        $newInvoice = Invoice::find($invoiceId);

        $this->validate_invoice_id($newInvoice->id);
        $this->validate_invoice_line_items_and_total($invoiceId, $draftInvoiceData['line_items']);
        $this->validate_record_is_latest_copy_in_table($newInvoice->client);
        $this->validate_record_is_latest_copy_in_table($newInvoice->senderAddress);
        $this->validate_record_is_latest_copy_in_table($newInvoice->clientAddress);
        $this->assertNotEquals($randomClient->id, $newInvoice->client->id);
        $this->assertNotEquals($randomSenderAddress->id, $newInvoice->senderAddress->id);
        $this->assertNotEquals($randomClientAddress->id, $newInvoice->clientAddress->id);
    }

    public function test_post_draft_has_no_required_fields_except_status(): void {
        $emptyInvoiceData = [];
        $response = $this->post(route('invoices.store'), $emptyInvoiceData);
        $response->assertStatus(422);

        $draftInvoiceData = [
            'status' => 'draft'
        ];
        $response = $this->post(route('invoices.store'), $draftInvoiceData);
        $response->assertStatus(201);
        $responseData = $response->json();
        $newRecordId = $responseData['invoice_id'];
        $this->validate_invoice_id($newRecordId);
        $this->validate_invoice_line_items_and_total($newRecordId, []);
        $this->assertDatabaseHas('invoices', ['id' => $newRecordId, 'total_cents' => 0]);
    }
    public function test_post_pending_creates_clients_and_addresses_when_they_do_not_already_exist(): void {
        $pendingInvoiceData = $this->createInvoicesRequestData(status: 'pending');
        $this->assertDatabaseMissing('clients',
            [
                'full_name' => $pendingInvoiceData['client_name'],
                'email' => $pendingInvoiceData['client_name']
            ]);
        $this->assertDatabaseMissing('addresses',
            [
                'street' => $pendingInvoiceData['client_address']['street'],
                'city' => $pendingInvoiceData['client_address']['city'],
                'postal_code' => $pendingInvoiceData['client_address']['postal_code'],
                'country' => $pendingInvoiceData['client_address']['country']
            ]);
        $this->assertDatabaseMissing('addresses',
            [
                'street' => $pendingInvoiceData['sender_address']['street'],
                'city' => $pendingInvoiceData['sender_address']['city'],
                'postal_code' => $pendingInvoiceData['sender_address']['postal_code'],
                'country' => $pendingInvoiceData['sender_address']['country']
            ]);

        $response = $this->post(route('invoices.store'), $pendingInvoiceData);
        // createInvoicesRequestData
        $response->assertStatus(201);
        $invoiceId = $response->json('invoice_id');
        $newInvoice = Invoice::find($invoiceId);

        $this->validate_invoice_id($newInvoice->id);
        $this->validate_invoice_line_items_and_total($invoiceId, $pendingInvoiceData['line_items']);
        $this->validate_record_is_latest_copy_in_table($newInvoice->client);
        $this->validate_record_is_latest_copy_in_table($newInvoice->senderAddress);
        $this->validate_record_is_latest_copy_in_table($newInvoice->clientAddress);
    }

    public function test_post_pending_finds_and_references_preexisting_clients_and_addresses(): void {
        // assert that even if we use data of an existing client and addresses in an invoice,
        // new client and address records are created.
        $randomClient = Client::inRandomOrder()
            ->whereNotNull('full_name')
            ->where('full_name', '!=', '')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->first();

        $randomSenderAddress = Address::inRandomOrder()
            ->whereNotNull('street')
            ->where('street', '!=', '')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->whereNotNull('postal_code')
            ->where('postal_code', '!=', '')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->first();
        // make sure addresses are different for when we use validate_record_is_last_copy_in_table()

        $randomClientAddress = Address::inRandomOrder()
            ->where('id', '!=', $randomSenderAddress->id)
            ->whereNotNull('street')
            ->where('street', '!=', '')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->whereNotNull('postal_code')
            ->where('postal_code', '!=', '')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->first();

        $draftInvoiceData = $this->createInvoicesRequestData(); // populate a draft
        // populate client and addresses with data that matches data already in the database
        $draftInvoiceData['client_name'] = $randomClient->full_name;
        $draftInvoiceData['client_email'] = $randomClient->email;
        $draftInvoiceData['sender_address']['street'] = $randomSenderAddress->street;
        $draftInvoiceData['sender_address']['city'] = $randomSenderAddress->city;
        $draftInvoiceData['sender_address']['postal_code'] = $randomSenderAddress->postal_code;
        $draftInvoiceData['sender_address']['country'] = $randomSenderAddress->country;
        $draftInvoiceData['client_address']['street'] = $randomClientAddress->street;
        $draftInvoiceData['client_address']['city'] = $randomClientAddress->city;
        $draftInvoiceData['client_address']['postal_code'] = $randomClientAddress->postal_code;
        $draftInvoiceData['client_address']['country'] = $randomClientAddress->country;

        // ensure there are duplicate copies of the same client and address records
        $this->post(route('invoices.store'), $draftInvoiceData);
        $pendingInvoiceData = $draftInvoiceData;
        $pendingInvoiceData['status'] = 'pending';
        // because status = pending, this invoice will reference the first client/address records
        $response = $this->post(route('invoices.store'), $pendingInvoiceData);
        $response->assertStatus(201);

        $invoiceId = $response->json('invoice_id');
        $newInvoice = Invoice::find($invoiceId);

        $this->validate_invoice_id($newInvoice->id);
        $this->validate_invoice_line_items_and_total($invoiceId, $draftInvoiceData['line_items']);
        $this->validate_record_is_oldest_copy_in_table($newInvoice->client);
        $this->validate_record_is_oldest_copy_in_table($newInvoice->senderAddress);
        $this->validate_record_is_oldest_copy_in_table($newInvoice->clientAddress);

    }

    /**
     * TODO: Explain this function and possibly grep the assertion to ensure 422 has a message about the specific key
     * also consider setting field to "" instead of unsetting, or doing "" then unset
     */
    public function test_post_pending_requires_all_fields_except_id(): void {
        $completeRequestData = $this->createInvoicesRequestData(status: 'pending');

        // show that post request is successful when "id" field is removed
        unset($completeRequestData['id']);
        $response = $this->post(route('invoices.store'), $completeRequestData);
        $response->assertStatus(201);

        $requestFields = array_keys($completeRequestData);
        foreach ($requestFields as $field) {
            // test unsetting nested keys
            if (is_array($completeRequestData[$field]) && array_keys($completeRequestData[$field]) !== range(0, count($completeRequestData[$field]) - 1)) {
                // if array keys are named (it's an associative array), we want to test unsetting
                // each of them to make sure all inner fields are required for pending invoices.
                $innerArray = $completeRequestData[$field];

                foreach (array_keys($innerArray) as $innerField) {
                    // make a fresh copy of the request data to test unsetting this field
                    $incompleteRequestData = $this->createInvoicesRequestData(status: 'pending');

                    $incompleteRequestData[$field][$innerField] = '';
                    $response = $this->post(route('invoices.store'), $incompleteRequestData);
                    $response->assertStatus(422);

                    unset($incompleteRequestData[$field][$innerField]);
                    $response = $this->post(route('invoices.store'), $incompleteRequestData);
                    $response->assertStatus(422);
                }

            } else if (is_array($completeRequestData[$field]) && is_array($completeRequestData[$field][0])) {
                // if array keys are numbered (a "regular" array), we make sure the 0th element is also an array and we want to test 
                // unsetting each of that element's inner keys to make sure all inner fields are required for pending invoices.
                $innerArray = $completeRequestData[$field][0];

                foreach (array_keys($innerArray) as $innerField) {
                    // make a fresh copy of the request data to test unsetting this field
                    $incompleteRequestData = $this->createInvoicesRequestData(status: 'pending');

                    $incompleteRequestData[$field][0][$innerField] = "";
                    $response = $this->post(route('invoices.store'), $incompleteRequestData);
                    $response->assertStatus(422);

                    unset($incompleteRequestData[$field][0][$innerField]);
                    $response = $this->post(route('invoices.store'), $incompleteRequestData);
                    $response->assertStatus(422);
                }
            }

            // make a fresh copy of the request data to test unsetting this field
            $incompleteRequestData = $this->createInvoicesRequestData(status: 'pending');

            $incompleteRequestData[$field] = "";
            $response = $this->post(route('invoices.store'), $incompleteRequestData);
            $response->assertStatus(422);
            
            unset($incompleteRequestData[$field]);
            $response = $this->post(route('invoices.store'), $incompleteRequestData);
            $response->assertStatus(422);
        }

        // finally, show that post accepts a complete form request when status is "pending".
        $response = $this->post(route('invoices.store'), $completeRequestData);
        $response->assertStatus(201);
    }

    public function test_post_paid_is_rejected(): void {
        // post will reject a complete invoice form with no missing fields if status is paid.
        $response = $this->post(route('invoices.store'), $this->createInvoicesRequestData(status: 'paid'));
        $response->assertStatus(422);
    }


    /*************************************************************************************
     *********** Test: GET /invoices/{id} (InvoiceController::show()) ********************  TODO: Make sure this is finished
     *************************************************************************************
     * Purpose:
     * - Verify retrieving a single invoice returns the invoice formatted by InvoiceResource.
     *
     * Assertions:
     * - HTTP 200
     * - JSON "data" contains an object with fields: id, issue_date, due_date, description,
     *   payment_terms, client_name, client_email, status, sender_address, client_address, line_items, total_cents.
     * - HTTP 404 on attempt to show non-existent invoice
     *
     */
    public function test_get_invoice_returns_expected_json_structure_and_code_200(): void {
        $randomInvoice = Invoice::inRandomOrder()->first();
        $response = $this->get(route('invoices.show', $randomInvoice));
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'issue_date',
                    'due_date',
                    'description',
                    'payment_terms',
                    'client_name',
                    'client_email',
                    'status',
                    'sender_address' => [
                        'street',
                        'city',
                        'postal_code',
                        'country'
                    ],
                    'client_address' => [
                        'street',
                        'city',
                        'postal_code',
                        'country'
                    ],
                    'line_items' => [
                        '*' => [
                            'id',
                            'name',
                            'quantity',
                            'price_unit_cents',
                            'price_total_cents'
                        ],
                    ],
                    'total_cents'
                ],
                'meta' => [
                    'api_version'
                ]
            ]);;

    }

    public function test_get_invoice_id_that_does_not_exist_returns_404(): void {
        $nonExistentInvoiceId = 'AAA000'; // this ID won't exist because it has three letters
        $response = $this->get(route('invoices.show', $nonExistentInvoiceId));
        $response->assertStatus(404);
    }

    /*************************************************************************************
     ******** Test: PUT/PATCH /invoices/{id} (InvoiceController::update()) ***************    TODO: Make sure this is finished
     *************************************************************************************
     * Purpose:
     * - Verify updating an invoice changes persisted fields and handles related records correctly.
     *
     * Minimal setup:
     * - Create an invoice (status draft or pending as required).
     * - Prepare an update payload that modifies simple attributes and possibly related client/address info.
     *
     * Assertions:
     * - HTTP 200 (or 204 depending on implementation)
     * - Database reflects updated invoice fields
     * - If client/address matching logic exists, ensure appropriate attach/update behavior occurred
     * - Response (if any) contains the updated InvoiceResource or appropriate status
     *
     * Edge cases:
     * - Attempt to update immutable fields (like id) -> should be rejected or ignored
     * - Update causing client/address to change -> ensure old client/address deleted only when no other invoices reference them
     */
    public function examples(): void {
        $var = 0;
        $randomAddress = Address::inRandomOrder()->where('id', '!=', $var)->first();
        $randomClient = Client::inRandomOrder()->where('id', '!=', $var)->first();
        $randomInvoice = Invoice::inRandomOrder()->where('status', 'draft')->first();
        
        // Note- any put pending should always reference first record in table
        // validate_invoice_line_items_and_total
        // validate_invoice_id
        // createInvoicesRequestData needs an update
    }

    public function test_put_draft_cannot_update_pending_or_paid_back_to_draft(): void {
        $randomPendingInvoice = Invoice::inRandomOrder()->where('status', 'pending')->first();
        $requestData = $this->createInvoicesRequestData(status: 'draft', invoice: $randomPendingInvoice);
        $requestData['client_email'] = "temp@hotmail.com";
        $response = $this->put(route('invoices.update', $randomPendingInvoice->id), $requestData);
        $response->assertStatus(422);

        $randomPaidInvoice = Invoice::inRandomOrder()->where('status', 'paid')->first();
        $requestData = $this->createInvoicesRequestData(status: 'draft', invoice: $randomPaidInvoice);
        $response = $this->put(route('invoices.update', $randomPaidInvoice->id), $requestData);
        $response->assertStatus(422);
    }

    public function test_put_draft_has_no_required_fields_except_status_and_id(): void {
        $randomId = Invoice::inRandomOrder()->where('status', 'draft')->first()->id;
        $requestData = [
            'status' => 'draft'
        ];
        $response = $this->put(route('invoices.update', $randomId), $requestData);
        $response->assertStatus(422);
    
        $requestData = [
            'id' => $randomId
        ];
        $response = $this->put(route('invoices.update', $randomId), $requestData);
        $response->assertStatus(422);
    
        $requestData = [
            'id' => $randomId,
            'status' => 'draft'
        ];
        $response = $this->put(route('invoices.update', $randomId), $requestData);
        $response->assertStatus(201);
    
        dd(Invoice::find($response->json('invoice_id'))->toJson());
    }

    public function _test_put_draft_creates_new_clients_and_addresses_when_other_invoices_use_current_clients_and_addresses(): void {
        // this test makes sure that a client or address is not updated when it would change the content of another invoice
        // find an invoice with a client and address that exists on another invoice.
        // update them to brand new clients and invoices
        // observe that the ids are different, the new id points to an appropriate record, and the old ids still reference the old values 

        // call validate_record_is_latest_copy_in_table
    }

    public function _test_put_draft_updates_current_clients_and_addresses_when_updates_do_not_affect_other_invoices(): void {
        // this should take an invoice with client/address that do not exist elsewhere and update those records to values that do not exist elsewhere
        // id should stay the same, indicating that the same referenced record was updated
    }

    public function _test_put_pending_creates_new_clients_and_addresses_when_necessary(): void {

        // call validate_record_is_latest_copy_in_table
    }

    public function _test_put_pending_erases_unused_client_and_address_records(): void {
        // this test should update a client/address record not referenced on another invoice
        // the updated values will match another record, and client/address id's will validate that

        // call validate_record_is_oldest_copy_in_table
    }

    public function _test_put_pending_requires_all_fields(): void {
        // iterate through as in post
    }

    public function _test_put_paid_has_no_required_fields_except_status_and_id(): void {

    }

    /*************************************************************************************
     ******** Test: DELETE /invoices/{id} (InvoiceController::destroy()) *****************
     *************************************************************************************
     * Purpose:
     * - Verify deleting an invoice removes the invoice and triggers the model's deleting
     *   event behavior (cascade or cleanup of clients/addresses when appropriate).
     *
     * Assertions:
     * - HTTP 204 (no content) or appropriate success status
     * - Invoice row no longer exists in DB
     * - If the invoice deletion logic deletes orphaned client/address records, assert they are deleted
     * - If client/address are referenced by other invoices, they must remain
     * - HTTP 404 on attempt to delete non-existent invoice
     *
     */
    public function test_delete_invoice_but_not_client_because_client_has_other_invoices(): void {

        $clientWithMultipleInvoices = Client::has('invoices', '>', 1)->first();
        $clientId = $clientWithMultipleInvoices->id;
        $invoiceThatBelongsToThatClient = $clientWithMultipleInvoices->invoices()->first();
        $invoiceId = $invoiceThatBelongsToThatClient->id;

        $response = $this->delete(route('invoices.destroy', $invoiceThatBelongsToThatClient));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseHas('clients', ['id' => $clientId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_delete_invoice_and_client_because_client_has_no_other_invoices(): void {

        $clientWithOneInvoice = Client::has('invoices', '=', 1)->first();
        $clientId = $clientWithOneInvoice->id;
        $invoiceThatBelongsToThatClient = $clientWithOneInvoice->invoices()->first();
        $invoiceId = $invoiceThatBelongsToThatClient->id;

        $response = $this->delete(route('invoices.destroy', $invoiceThatBelongsToThatClient));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseMissing('clients', ['id' => $clientId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_delete_invoice_but_not_address_because_address_is_on_other_invoices(): void {

        $addressOnMultipleInvoices = Address::has('clientInvoices', '>', 1)->first();
        $addressId = $addressOnMultipleInvoices->id;
        $invoiceWithThatAddress = $addressOnMultipleInvoices->clientInvoices()->first();
        $invoiceId = $invoiceWithThatAddress->id;

        $response = $this->delete(route('invoices.destroy', $invoiceWithThatAddress));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseHas('addresses', ['id' => $addressId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_delete_invoice_and_address_because_address_is_not_on_other_invoices(): void {

        $addressOnOneInvoice = Address::has('clientInvoices', '=', 1)->first();
        $addressId = $addressOnOneInvoice->id;
        $invoiceWithThatAddress = $addressOnOneInvoice->clientInvoices()->first();
        $invoiceId = $invoiceWithThatAddress->id;

        $response = $this->delete(route('invoices.destroy', $invoiceId));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseMissing('addresses', ['id' => $addressId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_delete_invoice_removes_its_line_items(): void {
        $invoiceWithLineItems = Invoice::has('lineItems', '>', 0)->inRandomOrder()->first();
        $invoiceId = $invoiceWithLineItems->id;
        $this->assertDatabaseHas('line_items', ['invoice_id' => $invoiceId]);
        $response = $this->delete(route('invoices.destroy', $invoiceWithLineItems));
        $response->assertStatus(204);
        $this->assertDatabaseMissing('line_items', ['invoice_id' => $invoiceId]);
    }

    public function test_delete_invoice_id_that_does_not_exist_returns_404(): void {

        $nonExistentInvoiceId = 'AAA000'; // this ID won't exist because it has three letters
        $response = $this->delete(route('invoices.destroy', $nonExistentInvoiceId));
        $response->assertStatus(404);
    }
}
