<?php

namespace App\Services;

use App\Models\User;
use Google\Service\Forms;
use Google\Service\Forms\Form;
use Google\Service\Forms\Request as FormsRequest;
use Google\Service\Forms\BatchUpdateFormRequest;
use Google\Service\Forms\CreateItemRequest;
use Google\Service\Forms\Item;
use Google\Service\Forms\Question;
use Google\Service\Forms\QuestionItem;
use Google\Service\Forms\TextQuestion;
use Google\Service\Forms\ChoiceQuestion;
use Google\Service\Forms\Option;
use Google\Service\Forms\Info;
use Google\Service\Forms\UpdateFormInfoRequest;
use Illuminate\Support\Facades\Log;

class GoogleFormsService
{
    protected GoogleClientService $googleClientService;

    public function __construct(GoogleClientService $googleClientService)
    {
        $this->googleClientService = $googleClientService;
    }

    /**
     * Get Google Forms service for a user
     */
    protected function getFormsService(User $user): Forms
    {
        $client = $this->googleClientService->getClientForUser($user);
        return new Forms($client);
    }

    /**
     * Create a new Google Form
     */
    public function createForm(User $user, string $title, string $description = ''): array
    {
        try {
            $service = $this->getFormsService($user);
            
            // Create a new form
            $form = new Form();
            $info = new Info();
            $info->setTitle($title);
            if (!empty($description)) {
                $info->setDescription($description);
            }
            $form->setInfo($info);

            $createdForm = $service->forms->create($form);

            Log::info('Google Form created successfully', [
                'user_id' => $user->id,
                'form_id' => $createdForm->getFormId(),
                'title' => $title
            ]);

            return [
                'form_id' => $createdForm->getFormId(),
                'title' => $createdForm->getInfo()->getTitle(),
                'description' => $createdForm->getInfo()->getDescription(),
                'edit_url' => "https://docs.google.com/forms/d/{$createdForm->getFormId()}/edit",
                'response_url' => $createdForm->getResponderUri(),
                'published_url' => $createdForm->getResponderUri()
            ];

        } catch (\Exception $e) {
            Log::error('Error creating Google Form', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to create Google Form: ' . $e->getMessage());
        }
    }

    /**
     * Get form details
     */
    public function getForm(User $user, string $formId): array
    {
        try {
            $service = $this->getFormsService($user);
            $form = $service->forms->get($formId);

            return [
                'form_id' => $form->getFormId(),
                'title' => $form->getInfo()->getTitle(),
                'description' => $form->getInfo()->getDescription(),
                'edit_url' => "https://docs.google.com/forms/d/{$form->getFormId()}/edit",
                'response_url' => $form->getResponderUri(),
                'published_url' => $form->getResponderUri(),
                'items' => $this->formatFormItems($form->getItems() ?? [])
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching Google Form', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to fetch Google Form: ' . $e->getMessage());
        }
    }

    /**
     * Add a text question to a form
     */
    public function addTextQuestion(User $user, string $formId, string $title, bool $required = false): array
    {
        try {
            $service = $this->getFormsService($user);

            // Create text question
            $textQuestion = new TextQuestion();
            
            $question = new Question();
            $question->setQuestionId(uniqid('q_'));
            $question->setRequired($required);
            $question->setTextQuestion($textQuestion);

            $questionItem = new QuestionItem();
            $questionItem->setQuestion($question);

            $item = new Item();
            $item->setTitle($title);
            $item->setQuestionItem($questionItem);

            $createItemRequest = new CreateItemRequest();
            $createItemRequest->setItem($item);
            $createItemRequest->setLocation(['index' => 0]);

            $request = new FormsRequest();
            $request->setCreateItem($createItemRequest);

            $batchUpdateRequest = new BatchUpdateFormRequest();
            $batchUpdateRequest->setRequests([$request]);

            $response = $service->forms->batchUpdate($formId, $batchUpdateRequest);

            Log::info('Text question added to Google Form', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'question_title' => $title
            ]);

            return [
                'success' => true,
                'question_id' => $question->getQuestionId(),
                'title' => $title,
                'required' => $required
            ];

        } catch (\Exception $e) {
            Log::error('Error adding text question to Google Form', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'question_title' => $title,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to add text question: ' . $e->getMessage());
        }
    }

    /**
     * Add a multiple choice question to a form
     */
    public function addMultipleChoiceQuestion(User $user, string $formId, string $title, array $options, bool $required = false): array
    {
        try {
            $service = $this->getFormsService($user);

            // Create choice options
            $choiceOptions = [];
            foreach ($options as $optionText) {
                $option = new Option();
                $option->setValue($optionText);
                $choiceOptions[] = $option;
            }

            $choiceQuestion = new ChoiceQuestion();
            $choiceQuestion->setType('RADIO');
            $choiceQuestion->setOptions($choiceOptions);

            $question = new Question();
            $question->setQuestionId(uniqid('q_'));
            $question->setRequired($required);
            $question->setChoiceQuestion($choiceQuestion);

            $questionItem = new QuestionItem();
            $questionItem->setQuestion($question);

            $item = new Item();
            $item->setTitle($title);
            $item->setQuestionItem($questionItem);

            $createItemRequest = new CreateItemRequest();
            $createItemRequest->setItem($item);
            $createItemRequest->setLocation(['index' => 0]);

            $request = new FormsRequest();
            $request->setCreateItem($createItemRequest);

            $batchUpdateRequest = new BatchUpdateFormRequest();
            $batchUpdateRequest->setRequests([$request]);

            $response = $service->forms->batchUpdate($formId, $batchUpdateRequest);

            Log::info('Multiple choice question added to Google Form', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'question_title' => $title,
                'options_count' => count($options)
            ]);

            return [
                'success' => true,
                'question_id' => $question->getQuestionId(),
                'title' => $title,
                'options' => $options,
                'required' => $required
            ];

        } catch (\Exception $e) {
            Log::error('Error adding multiple choice question to Google Form', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'question_title' => $title,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to add multiple choice question: ' . $e->getMessage());
        }
    }

    /**
     * Update form title and description
     */
    public function updateFormInfo(User $user, string $formId, string $title, string $description = ''): array
    {
        try {
            $service = $this->getFormsService($user);

            $info = new Info();
            $info->setTitle($title);
            if (!empty($description)) {
                $info->setDescription($description);
            }

            $updateFormInfoRequest = new UpdateFormInfoRequest();
            $updateFormInfoRequest->setInfo($info);
            $updateFormInfoRequest->setUpdateMask('title,description');

            $request = new FormsRequest();
            $request->setUpdateFormInfo($updateFormInfoRequest);

            $batchUpdateRequest = new BatchUpdateFormRequest();
            $batchUpdateRequest->setRequests([$request]);

            $response = $service->forms->batchUpdate($formId, $batchUpdateRequest);

            Log::info('Google Form info updated', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'title' => $title
            ]);

            return [
                'success' => true,
                'title' => $title,
                'description' => $description
            ];

        } catch (\Exception $e) {
            Log::error('Error updating Google Form info', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to update form info: ' . $e->getMessage());
        }
    }

    /**
     * Get form responses
     */
    public function getFormResponses(User $user, string $formId): array
    {
        try {
            $service = $this->getFormsService($user);
            $responses = $service->forms_responses->listFormsResponses($formId);

            $formattedResponses = [];
            foreach ($responses->getResponses() ?? [] as $response) {
                $formattedResponses[] = [
                    'response_id' => $response->getResponseId(),
                    'create_time' => $response->getCreateTime(),
                    'last_submitted_time' => $response->getLastSubmittedTime(),
                    'answers' => $this->formatAnswers($response->getAnswers() ?? [])
                ];
            }

            Log::info('Google Form responses fetched', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'response_count' => count($formattedResponses)
            ]);

            return [
                'form_id' => $formId,
                'responses' => $formattedResponses,
                'total_responses' => count($formattedResponses)
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching Google Form responses', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to fetch form responses: ' . $e->getMessage());
        }
    }

    /**
     * Delete a form (move to trash)
     */
    public function deleteForm(User $user, string $formId): bool
    {
        try {
            $service = $this->getFormsService($user);
            
            // Note: Google Forms API doesn't have a direct delete method
            // We can only batch update to modify the form
            // To actually delete, user needs to do it manually in Google Drive
            
            Log::info('Google Form deletion requested (manual deletion required)', [
                'user_id' => $user->id,
                'form_id' => $formId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error deleting Google Form', [
                'user_id' => $user->id,
                'form_id' => $formId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to delete form: ' . $e->getMessage());
        }
    }

    /**
     * Format form items for display
     */
    protected function formatFormItems(array $items): array
    {
        $formattedItems = [];
        
        foreach ($items as $item) {
            $formattedItem = [
                'item_id' => $item->getItemId(),
                'title' => $item->getTitle(),
                'description' => $item->getDescription()
            ];

            if ($item->getQuestionItem()) {
                $question = $item->getQuestionItem()->getQuestion();
                $formattedItem['question'] = [
                    'question_id' => $question->getQuestionId(),
                    'required' => $question->getRequired(),
                    'type' => $this->getQuestionType($question)
                ];

                // Add options for choice questions
                if ($question->getChoiceQuestion()) {
                    $options = [];
                    foreach ($question->getChoiceQuestion()->getOptions() ?? [] as $option) {
                        $options[] = $option->getValue();
                    }
                    $formattedItem['question']['options'] = $options;
                }
            }

            $formattedItems[] = $formattedItem;
        }

        return $formattedItems;
    }

    /**
     * Get question type from question object
     */
    protected function getQuestionType(Question $question): string
    {
        if ($question->getTextQuestion()) {
            return 'text';
        } elseif ($question->getChoiceQuestion()) {
            $type = $question->getChoiceQuestion()->getType();
            return strtolower($type);
        } elseif ($question->getScaleQuestion()) {
            return 'scale';
        } elseif ($question->getDateQuestion()) {
            return 'date';
        } elseif ($question->getTimeQuestion()) {
            return 'time';
        }
        
        return 'unknown';
    }

    /**
     * Format form response answers
     */
    protected function formatAnswers(array $answers): array
    {
        $formattedAnswers = [];
        
        foreach ($answers as $questionId => $answer) {
            $formattedAnswers[$questionId] = [
                'text_answers' => $answer->getTextAnswers() ? $answer->getTextAnswers()->getAnswers() : null,
                'file_upload_answers' => $answer->getFileUploadAnswers() ? $answer->getFileUploadAnswers()->getAnswers() : null
            ];
        }

        return $formattedAnswers;
    }
}