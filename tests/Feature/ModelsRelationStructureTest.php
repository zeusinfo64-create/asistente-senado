<?php

namespace Tests\Feature;

use App\Models\AiAnalysis;
use App\Models\Asset;
use App\Models\AssetState;
use App\Models\AssetType;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\DiagnosticOption;
use App\Models\DiagnosticQuestion;
use App\Models\InterventionPart;
use App\Models\InterventionType;
use App\Models\Notification;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\SlaPolicy;
use App\Models\SparePart;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketDiagnosticAnswer;
use App\Models\TicketEvent;
use App\Models\TicketIntervention;
use App\Models\TicketPriority;
use App\Models\TicketRating;
use App\Models\TicketSequence;
use App\Models\TicketStatus;
use App\Models\TicketStatusTransition;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModelsRelationStructureTest extends TestCase
{
    private mixed $mysqlConnection;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'helpdesk_v11');
        DB::purge('mysql');

        $this->mysqlConnection = DB::connection('mysql');
        $this->mysqlConnection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->mysqlConnection->rollBack();
        DB::purge('mysql');

        parent::tearDown();
    }

    /**
     * Crea el grafo mínimo de datos dentro de la transacción de la prueba.
     *
     * @return array<string, object>
     */
    private function seedGraph(): array
    {
        $unitType = new OrganizationalUnitType;
        $unitType->code = 'RELTEST';
        $unitType->name = 'Unidad de prueba';
        $unitType->save();

        $unit = new OrganizationalUnit;
        $unit->organizational_unit_type_id = $unitType->id;
        $unit->code = 'UT-RELTEST';
        $unit->name = 'Área de prueba';
        $unit->save();

        $role = new Role;
        $role->code = 'RELTEST';
        $role->name = 'Rol de prueba';
        $role->save();

        $requester = new User;
        $requester->username = 'solicitante.reltest';
        $requester->first_name = 'Solicitante';
        $requester->last_name = 'De Prueba';
        $requester->email = 'solicitante.reltest@example.com';
        $requester->organizational_unit_id = $unit->id;
        $requester->save();

        $technician = new User;
        $technician->username = 'tecnico.reltest';
        $technician->first_name = 'Técnico';
        $technician->last_name = 'De Prueba';
        $technician->email = 'tecnico.reltest@example.com';
        $technician->organizational_unit_id = $unit->id;
        $technician->save();

        $requester->roles()->attach($role->id);

        $ticketType = new TicketType;
        $ticketType->code = 'RELTEST';
        $ticketType->name = 'Tipo de prueba';
        $ticketType->save();

        $status = new TicketStatus;
        $status->code = 'RELTEST';
        $status->name = 'Estado de prueba';
        $status->is_initial = true;
        $status->save();

        $priority = new TicketPriority;
        $priority->code = 'RELTEST';
        $priority->name = 'Prioridad de prueba';
        $priority->save();

        $parentCategory = new Category;
        $parentCategory->code = 'RELTEST';
        $parentCategory->name = 'Categoría padre de prueba';
        $parentCategory->save();

        $childCategory = new Category;
        $childCategory->parent_id = $parentCategory->id;
        $childCategory->code = 'RELTEST-CHILD';
        $childCategory->name = 'Subcategoría de prueba';
        $childCategory->save();

        $assetType = new AssetType;
        $assetType->code = 'RELTEST';
        $assetType->name = 'Tipo de activo de prueba';
        $assetType->save();

        $assetState = new AssetState;
        $assetState->code = 'RELTEST';
        $assetState->name = 'Estado de activo de prueba';
        $assetState->save();

        $asset = new Asset;
        $asset->asset_code = 'AST-RELTEST-0001';
        $asset->asset_type_id = $assetType->id;
        $asset->asset_state_id = $assetState->id;
        $asset->save();

        $ticket = new Ticket;
        $ticket->ticket_number = 'TCK-RELTEST-0001';
        $ticket->ticket_type_id = $ticketType->id;
        $ticket->requester_id = $requester->id;
        $ticket->created_by_id = $requester->id;
        $ticket->organizational_unit_id = $unit->id;
        $ticket->category_id = $parentCategory->id;
        $ticket->subcategory_id = $childCategory->id;
        $ticket->priority_id = $priority->id;
        $ticket->status_id = $status->id;
        $ticket->asset_id = $asset->id;
        $ticket->assigned_to_id = $technician->id;
        $ticket->subject = 'Asunto de prueba de relaciones';
        $ticket->description = 'Descripción de prueba de relaciones';
        $ticket->save();
        $event = new TicketEvent;
        $event->ticket_id = $ticket->id;
        $event->actor_id = $requester->id;
        $event->event_type = 'created';
        $event->to_status_id = $status->id;
        $event->save();

        $comment = new TicketComment;
        $comment->ticket_id = $ticket->id;
        $comment->author_id = $technician->id;
        $comment->body = 'Comentario de prueba';
        $comment->save();

        $interventionType = new InterventionType;
        $interventionType->code = 'RELTEST';
        $interventionType->name = 'Intervención de prueba';
        $interventionType->save();

        $intervention = new TicketIntervention;
        $intervention->ticket_id = $ticket->id;
        $intervention->technician_id = $technician->id;
        $intervention->intervention_type_id = $interventionType->id;
        $intervention->save();

        $attachment = new Attachment;
        $attachment->attachable_type = $ticket->getMorphClass();
        $attachment->attachable_id = $ticket->id;
        $attachment->original_name = 'prueba.txt';
        $attachment->stored_name = 'reltest-prueba.txt';
        $attachment->path = 'tickets/reltest-prueba.txt';
        $attachment->disk = 'local';
        $attachment->mime_type = 'text/plain';
        $attachment->size_bytes = 7;
        $attachment->uploaded_by_id = $technician->id;
        $attachment->save();

        $commentAttachment = new Attachment;
        $commentAttachment->attachable_type = $comment->getMorphClass();
        $commentAttachment->attachable_id = $comment->id;
        $commentAttachment->original_name = 'comentario.txt';
        $commentAttachment->stored_name = 'reltest-comentario.txt';
        $commentAttachment->path = 'comments/reltest-comentario.txt';
        $commentAttachment->disk = 'local';
        $commentAttachment->mime_type = 'text/plain';
        $commentAttachment->size_bytes = 11;
        $commentAttachment->uploaded_by_id = $technician->id;
        $commentAttachment->save();

        $rating = new TicketRating;
        $rating->ticket_id = $ticket->id;
        $rating->user_id = $requester->id;
        $rating->technician_id = $technician->id;
        $rating->score = 5;
        $rating->save();

        $aiAnalysis = new AiAnalysis;
        $aiAnalysis->analyzable_type = $ticket->getMorphClass();
        $aiAnalysis->analyzable_id = $ticket->id;
        $aiAnalysis->analysis_type = 'classification';
        $aiAnalysis->provider = 'openai';
        $aiAnalysis->model = 'gpt-test';
        $aiAnalysis->status = 'pending';
        $aiAnalysis->suggested_category_id = $parentCategory->id;
        $aiAnalysis->suggested_subcategory_id = $childCategory->id;
        $aiAnalysis->suggested_priority_id = $priority->id;
        $aiAnalysis->applied_by_id = $technician->id;
        $aiAnalysis->analyzed_at = now();
        $aiAnalysis->save();

        $slaPolicy = new SlaPolicy;
        $slaPolicy->code = 'RELTEST';
        $slaPolicy->name = 'SLA de prueba';
        $slaPolicy->priority_id = $priority->id;
        $slaPolicy->category_id = $parentCategory->id;
        $slaPolicy->organizational_unit_id = $unit->id;
        $slaPolicy->ticket_type_id = $ticketType->id;
        $slaPolicy->response_hours = 4.00;
        $slaPolicy->resolution_hours = 24.00;
        $slaPolicy->save();

        $ticket->sla_policy_id = $slaPolicy->id;
        $ticket->save();

        $sparePart = new SparePart;
        $sparePart->code = 'RELTEST';
        $sparePart->name = 'Repuesto de prueba';
        $sparePart->unit = 'unidad';
        $sparePart->save();

        $interventionPart = new InterventionPart;
        $interventionPart->intervention_id = $intervention->id;
        $interventionPart->spare_part_id = $sparePart->id;
        $interventionPart->quantity = 1.00;
        $interventionPart->unit = 'unidad';
        $interventionPart->save();

        $diagnosticQuestion = new DiagnosticQuestion;
        $diagnosticQuestion->category_id = $parentCategory->id;
        $diagnosticQuestion->question = '¿Pregunta de prueba?';
        $diagnosticQuestion->save();

        $diagnosticOption = new DiagnosticOption;
        $diagnosticOption->question_id = $diagnosticQuestion->id;
        $diagnosticOption->label = 'Opción de prueba';
        $diagnosticOption->suggested_category_id = $childCategory->id;
        $diagnosticOption->save();

        $diagnosticAnswer = new TicketDiagnosticAnswer;
        $diagnosticAnswer->ticket_id = $ticket->id;
        $diagnosticAnswer->question_id = $diagnosticQuestion->id;
        $diagnosticAnswer->option_id = $diagnosticOption->id;
        $diagnosticAnswer->answered_by_id = $technician->id;
        $diagnosticAnswer->answered_at = now();
        $diagnosticAnswer->save();

        $transition = new TicketStatusTransition;
        $transition->from_status_id = $status->id;
        $transition->to_status_id = $status->id;
        $transition->allowed_role_id = $role->id;
        $transition->requires_reason = false;
        $transition->save();

        $setting = new Setting;
        $setting->key = 'reltest.setting';
        $setting->value = 'valor de prueba';
        $setting->type = 'string';
        $setting->updated_by_id = $technician->id;
        $setting->save();

        // La Fase 4 (seeders) ya crea la secuencia del año en curso: dentro de la
        // transacción de la prueba se reemplaza para poder insertar el registro
        // propio sin violar UQ(year). El rollback la restaura.
        $ticketSequence = new TicketSequence;
        $ticketSequence->year = (int) now()->format('Y');
        TicketSequence::query()->where('year', $ticketSequence->year)->delete();
        $ticketSequence->last_number = 1;
        $ticketSequence->save();

        $notification = new Notification;
        $notification->type = 'App\\Notifications\\TestNotification';
        $notification->channel = 'database';
        $notification->notifiable_type = $requester->getMorphClass();
        $notification->notifiable_id = $requester->id;
        $notification->data = ['mensaje' => 'Notificación de prueba'];
        $notification->save();

        $auditLog = new AuditLog;
        $auditLog->actor_id = $technician->id;
        $auditLog->action = 'created';
        $auditLog->auditable_type = $ticket->getMorphClass();
        $auditLog->auditable_id = $ticket->id;
        $auditLog->save();

        return [
            'unit' => $unit,
            'role' => $role,
            'requester' => $requester,
            'technician' => $technician,
            'parentCategory' => $parentCategory,
            'childCategory' => $childCategory,
            'status' => $status,
            'priority' => $priority,
            'ticketType' => $ticketType,
            'asset' => $asset,
            'ticket' => $ticket,
            'event' => $event,
            'comment' => $comment,
            'intervention' => $intervention,
            'attachment' => $attachment,
            'commentAttachment' => $commentAttachment,
            'rating' => $rating,
            'aiAnalysis' => $aiAnalysis,
            'notification' => $notification,
            'auditLog' => $auditLog,
            'slaPolicy' => $slaPolicy,
            'sparePart' => $sparePart,
            'interventionPart' => $interventionPart,
            'diagnosticQuestion' => $diagnosticQuestion,
            'diagnosticOption' => $diagnosticOption,
            'diagnosticAnswer' => $diagnosticAnswer,
            'transition' => $transition,
            'setting' => $setting,
            'ticketSequence' => $ticketSequence,
        ];
    }

    public function test_user_relations_to_role_and_organizational_unit(): void
    {
        $g = $this->seedGraph();

        $roles = $g['requester']->roles;

        $this->assertCount(1, $roles);
        $this->assertSame($g['role']->id, $roles->first()->id);
        $this->assertSame($g['requester']->id, $roles->first()->users->first()->id);
        $this->assertSame($g['unit']->id, $g['requester']->organizationalUnit->id);
        $this->assertTrue($g['requester']->organizationalUnit->users->pluck('id')->contains($g['requester']->id));
    }

    public function test_ticket_relations_to_master_and_classification_entities(): void
    {
        $g = $this->seedGraph();
        $ticket = $g['ticket'];

        $this->assertInstanceOf(User::class, $ticket->requester);
        $this->assertSame($g['requester']->id, $ticket->requester->id);

        $this->assertInstanceOf(User::class, $ticket->assignedTechnician);
        $this->assertSame($g['technician']->id, $ticket->assignedTechnician->id);

        $this->assertSame($g['parentCategory']->id, $ticket->category->id);
        $this->assertSame($g['childCategory']->id, $ticket->subcategory->id);
        $this->assertSame($g['parentCategory']->id, $ticket->subcategory->parent->id);
        $this->assertSame($ticket->id, $ticket->category->tickets->first()->id);
        $this->assertSame($ticket->id, $ticket->subcategory->subcategoryTickets->first()->id);

        $this->assertSame('RELTEST', $ticket->status->code);
        $this->assertSame('RELTEST', $ticket->priority->code);
        $this->assertSame('RELTEST', $ticket->ticketType->code);
        $this->assertSame($g['asset']->id, $ticket->asset->id);
        $this->assertSame($g['requester']->id, $ticket->createdBy->id);
        $this->assertSame($g['unit']->id, $ticket->organizationalUnit->id);

        $this->assertSame($ticket->id, $g['asset']->tickets->first()->id);
        $this->assertSame($ticket->id, $g['status']->tickets->first()->id);
        $this->assertSame($ticket->id, $g['priority']->tickets->first()->id);
    }

    public function test_ticket_relations_to_dependent_entities(): void
    {
        $g = $this->seedGraph();
        $ticket = $g['ticket'];

        $this->assertSame($g['event']->id, $ticket->events->first()->id);
        $this->assertInstanceOf(User::class, $ticket->events->first()->actor);
        $this->assertInstanceOf(TicketStatus::class, $ticket->events->first()->toStatus);

        $this->assertSame($g['comment']->id, $ticket->comments->first()->id);
        $this->assertSame($g['technician']->id, $ticket->comments->first()->author->id);

        $this->assertSame($g['intervention']->id, $ticket->interventions->first()->id);
        $this->assertSame($g['technician']->id, $ticket->interventions->first()->technician->id);
        $this->assertSame($g['intervention']->id, $g['technician']->interventions->first()->id);

        $this->assertSame($g['attachment']->id, $ticket->attachments->first()->id);
        $this->assertInstanceOf(InterventionType::class, $g['intervention']->interventionType);

        $this->assertInstanceOf(TicketRating::class, $ticket->rating);
        $this->assertSame($g['rating']->id, $ticket->rating->id);
        $this->assertSame($g['requester']->id, $ticket->rating->user->id);
        $this->assertSame($g['technician']->id, $ticket->rating->technician->id);

        $this->assertSame($g['aiAnalysis']->id, $ticket->aiAnalyses->first()->id);
        $this->assertSame($g['parentCategory']->id, $ticket->aiAnalyses->first()->suggestedCategory->id);
        $this->assertSame($g['priority']->id, $ticket->aiAnalyses->first()->suggestedPriority->id);
        $this->assertSame($g['technician']->id, $g['aiAnalysis']->appliedBy->id);

        $this->assertSame($ticket->id, $g['event']->ticket->id);
        $this->assertSame($ticket->id, $g['comment']->ticket->id);
        $this->assertSame($ticket->id, $g['intervention']->ticket->id);
    }

    public function test_category_hierarchy_relations(): void
    {
        $g = $this->seedGraph();

        $this->assertSame($g['parentCategory']->id, $g['childCategory']->parent->id);
        $this->assertSame($g['childCategory']->id, $g['parentCategory']->children->first()->id);
        $this->assertNull($g['parentCategory']->parent);
    }

    public function test_polymorphic_owner_relations(): void
    {
        $g = $this->seedGraph();

        $this->assertInstanceOf(Ticket::class, $g['attachment']->attachable);
        $this->assertSame($g['ticket']->id, $g['attachment']->attachable->id);
        $this->assertSame($g['technician']->id, $g['attachment']->uploadedBy->id);

        $this->assertInstanceOf(Ticket::class, $g['auditLog']->auditable);
        $this->assertSame($g['ticket']->id, $g['auditLog']->auditable->id);
        $this->assertSame($g['technician']->id, $g['auditLog']->actor->id);

        $this->assertSame($g['comment']->id, $g['commentAttachment']->attachable->id);
        $this->assertSame($g['commentAttachment']->id, $g['comment']->attachments->first()->id);

        $this->assertInstanceOf(User::class, $g['notification']->notifiable);
        $this->assertSame($g['requester']->id, $g['notification']->notifiable->id);
        $this->assertSame($g['notification']->id, $g['requester']->notifications->first()->id);
    }

    public function test_relations_of_remaining_models(): void
    {
        $g = $this->seedGraph();

        $this->assertSame($g['technician']->id, $g['setting']->updatedBy->id);

        $this->assertSame($g['interventionPart']->id, $g['intervention']->parts->first()->id);
        $this->assertSame($g['sparePart']->id, $g['interventionPart']->sparePart->id);
        $this->assertSame($g['interventionPart']->id, $g['sparePart']->interventionParts->first()->id);

        $this->assertSame($g['parentCategory']->id, $g['diagnosticQuestion']->category->id);
        $this->assertSame($g['diagnosticOption']->id, $g['diagnosticQuestion']->options->first()->id);
        $this->assertSame($g['diagnosticQuestion']->id, $g['diagnosticOption']->question->id);
        $this->assertSame($g['childCategory']->id, $g['diagnosticOption']->suggestedCategory->id);
        $this->assertSame($g['diagnosticAnswer']->id, $g['diagnosticQuestion']->answers->first()->id);
        $this->assertSame($g['diagnosticAnswer']->id, $g['diagnosticOption']->answers->first()->id);
        $this->assertSame($g['ticket']->id, $g['diagnosticAnswer']->ticket->id);
        $this->assertSame($g['technician']->id, $g['diagnosticAnswer']->answeredBy->id);

        $this->assertSame($g['status']->id, $g['transition']->fromStatus->id);
        $this->assertSame($g['status']->id, $g['transition']->toStatus->id);
        $this->assertSame($g['role']->id, $g['transition']->allowedRole->id);

        $this->assertSame($g['priority']->id, $g['slaPolicy']->priority->id);
        $this->assertSame($g['parentCategory']->id, $g['slaPolicy']->category->id);
        $this->assertSame($g['unit']->id, $g['slaPolicy']->organizationalUnit->id);
        $this->assertSame($g['ticketType']->id, $g['slaPolicy']->ticketType->id);
        $this->assertSame($g['ticket']->id, $g['slaPolicy']->tickets->first()->id);
        $this->assertSame($g['slaPolicy']->id, $g['ticket']->slaPolicy->id);
        $this->assertSame($g['slaPolicy']->id, $g['priority']->slaPolicies->first()->id);

        $this->assertNull($g['ticketSequence']->created_at);
        $this->assertNotNull($g['ticketSequence']->updated_at);
    }
}
