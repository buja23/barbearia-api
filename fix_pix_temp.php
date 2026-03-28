<?php
// Script temporario para substituir generateLocalPixPayment no PaymentService.php

$file = '/var/www/html/app/Services/PaymentService.php';
$content = file_get_contents($file);
if ($content === false) {
    echo "ERRO: nao foi possivel ler o arquivo\n";
    exit(1);
}

// Encontra onde começa o método generateLocalPixPayment
$start = strpos($content, '    public function generateLocalPixPayment(');
if ($start === false) {
    echo "ERRO: metodo generateLocalPixPayment nao encontrado\n";
    exit(1);
}

// O fechamento da classe é o último "\n}" do arquivo
$classEnd = strrpos($content, "\n}");
if ($classEnd === false) {
    echo "ERRO: fechamento da classe nao encontrado\n";
    exit(1);
}

echo "start=$start classEnd=$classEnd totalLen=" . strlen($content) . "\n";

// Pega o trecho antes do método
$before = substr($content, 0, $start);

$newMethod = '    public function generateLocalPixPayment(Appointment $appointment): array
    {
        $barbershop = $appointment->barbershop;

        if (empty($barbershop?->pix_key)) {
            return [
                \'success\' => false,
                \'error\'   => \'Esta barbearia nao configurou uma chave PIX. Acesse Configuracoes > Minha Barbearia.\',
            ];
        }

        if ($appointment->total_price <= 0) {
            return [\'success\' => false, \'error\' => \'Valor do agendamento invalido (R$ 0,00).\'];
        }

        try {
            $typeMap = [
                \'cpf\'    => Parser::KEY_TYPE_DOCUMENT,
                \'cnpj\'   => Parser::KEY_TYPE_DOCUMENT,
                \'email\'  => Parser::KEY_TYPE_EMAIL,
                \'phone\'  => Parser::KEY_TYPE_PHONE,
                \'random\' => Parser::KEY_TYPE_RANDOM,
            ];
            $keyType = $typeMap[$barbershop->pix_key_type ?? \'email\'] ?? Parser::KEY_TYPE_EMAIL;

            $payload = (new StaticPayload())
                ->setMerchantName($barbershop->name)
                ->setMerchantCity(\'Brasil\')
                ->setPixKey($keyType, $barbershop->pix_key)
                ->setAmount((float) $appointment->total_price)
                ->getPixCode();

            $png    = \SimpleSoftwareIO\QrCode\Facades\QrCode::format(\'png\')->size(300)->margin(2)->generate($payload);
            $imgSrc = \'data:image/png;base64,\' . base64_encode($png);

            $appointment->update([
                \'payment_method\'   => \'pix\',
                \'payment_status\'   => \'pending\',
                \'pix_copy_paste\'   => $payload,
                \'pix_qr_code_url\'  => $imgSrc,
            ]);

            Log::channel(\'audit\')->info(\'PIX estatico gerado\', [
                \'appointment_id\' => $appointment->id,
                \'barbershop_id\'  => $barbershop->id,
                \'amount\'         => $appointment->total_price,
            ]);

            return [\'success\' => true];

        } catch (\Exception $e) {
            Log::error(\'Erro ao gerar PIX estatico: \' . $e->getMessage());
            return [\'success\' => false, \'error\' => $e->getMessage()];
        }
    }
';

$newContent = $before . $newMethod . "\n}";

$written = file_put_contents($file, $newContent);
if ($written === false) {
    echo "ERRO: nao foi possivel escrever o arquivo\n";
    exit(1);
}

echo "OK: arquivo atualizado ($written bytes)\n";
