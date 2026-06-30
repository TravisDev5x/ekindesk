<?php

namespace App\Services\Aws;

class AwsCloudWatchService
{
    // TODO: Sprint 2
    // Procesa eventos de AWS CloudWatch + EventBridge como tickets automáticos.
    //
    // parseWebhookPayload(array $payload): array
    //   Normaliza el SNS/EventBridge envelope a estructura interna.
    //
    // mapToTicketData(array $event): array
    //   Convierte evento de CloudWatch Alarm a campos de Ticket:
    //   - AlarmName → subject
    //   - AlarmDescription → body
    //   - StateChangeTime → created_at
    //   - severity según métrica
    //
    // Requiere: AWS_SNS_TOPIC_ARN, validación de firma SNS en InboundMailController
}
