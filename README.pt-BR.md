# Biblioteca PHP para Validação de Cartão de Crédito

## Visão Geral

Este projeto fornece uma biblioteca PHP para validar detalhes de cartões de crédito. Ele foca em diversos aspectos chave da validação: o algoritmo de Luhn para integridade do número do cartão, identificação do tipo de cartão (Visa e Mastercard), verificação da data de expiração e validação do CVV. A biblioteca é projetada com uma abordagem modular, utilizando o padrão Strategy para seus validadores, tornando-a extensível. Ela fornece relatórios detalhados sobre os resultados da validação.

Tipos de cartão atualmente suportados:
*   Visa
*   Mastercard

## Funcionalidades

*   **Verificação do Algoritmo de Luhn**: Valida os números dos cartões contra o algoritmo de Luhn (Mod 10).
*   **Identificação do Tipo de Cartão**: Identifica e valida números de cartão pertencentes a Visa e Mastercard.
*   **Validação da Data de Expiração**: Garante que a data de expiração do cartão não passou e que o mês/ano são válidos. Os cartões são considerados válidos durante todo o mês de expiração.
*   **Validação do CVV**: Valida o CVV (Código de Verificação do Cartão). Para Visa e Mastercard, um CVV de 3 dígitos é esperado. Para tipos de cartão desconhecidos, a validação do CVV é atualmente ignorada.
*   **Relatório Detalhado de Validação**: O método `getValidationReport()` retorna um relatório abrangente incluindo um status geral de validade, resultados de cada validador individual e uma coleção de mensagens de erro.
*   **Design Extensível**: Utiliza o padrão Strategy (`ValidatorInterface`), permitindo que novas regras de validação (e.g., para outros tipos de cartão ou verificações personalizadas) sejam adicionadas facilmente. Cada validador retorna um array de resultado detalhado.

## Requisitos

*   PHP 8.3 ou superior
*   Composer

## Instalação

1.  **Clone o repositório (se aplicável):**
    ```bash
    git clone <url-do-repositorio>
    cd <diretorio-do-projeto>
    ```

2.  **Instale as dependências usando o Composer:**
    Isso instalará o PHPUnit, que é usado para executar os testes.
    ```bash
    composer install
    ```

    Se você fosse publicar esta biblioteca no Packagist, você normalmente a exigiria em seu projeto via:
    ```bash
    composer require app/project
    ```
    (Assumindo que `app/project` é o nome definido no `composer.json`. Para uma biblioteca real, este seria o nome do seu vendor/pacote).

## Como Usar

Para validar um cartão de crédito, crie uma instância de `CreditCard` e então chame o método `getValidationReport()` para resultados detalhados.

```php
<?php

require 'vendor/autoload.php'; // Se instalado via Composer

use App\CreditCard;

// Exemplo de detalhes (substitua com dados reais do cartão para testes)
$cardNumber = '49927398717'; // Luhn inválido
$cardHolderName = 'Maria Silva';
$expirationMonth = 1; // Janeiro
$expirationYear = (int)date('Y') - 1; // Ano passado
$cvv = '12'; // CVV inválido para Visa (se fosse um Visa)

$cartao = new CreditCard(
    $cardNumber,
    $cardHolderName,
    $expirationMonth,
    $expirationYear,
    $cvv
);

$relatorio = $cartao->getValidationReport();

echo "Relatório de Validação:\n";
echo "Geralmente Válido: " . ($relatorio['overall_valid'] ? 'Sim' : 'Não') . "\n\n";

echo "Detalhes:\n";
foreach ($relatorio['details'] as $nomeValidador => $resultado) {
    echo "- " . ucfirst(str_replace('_', ' ', $nomeValidador)) . ": " . ($resultado['valid'] ? 'Válido' : 'Inválido');
    if (!empty($resultado['message'])) {
        echo " (Mensagem: " . htmlspecialchars($resultado['message']) . ")";
    }
    echo "\n";
}
echo "\n";

if (!$relatorio['overall_valid']) {
    echo "Mensagens de Erro:\n";
    if (empty($relatorio['messages'])) {
        echo "- Nenhuma mensagem de erro específica (verifique os detalhes para resultados individuais dos validadores).\n";
    } else {
        foreach ($relatorio['messages'] as $mensagem) {
            echo "- " . htmlspecialchars($mensagem) . "\n";
        }
    }
}

/*
Exemplo da estrutura de $relatorio:

[
    'overall_valid' => false,
    'details' => [
        'luhn' => ['valid' => false, 'message' => 'Soma de verificação Luhn inválida.'],
        'card_type' => ['valid' => true, 'message' => ''], // Assumindo que o número do cartão começa com um prefixo válido como '4'
        'expiration_date' => ['valid' => false, 'message' => 'Cartão expirou. O ano de expiração está no passado.'],
        'cvv' => ['valid' => false, 'message' => 'Tamanho de CVV inválido para Visa/Mastercard. Deve ter 3 dígitos.']
    ],
    'messages' => [
        'Soma de verificação Luhn inválida.',
        'Cartão expirou. O ano de expiração está no passado.',
        'Tamanho de CVV inválido para Visa/Mastercard. Deve ter 3 dígitos.'
    ]
]
*/

// Para uma verificação booleana simples da validade geral, você pode usar isValid():
$eVálidoSimples = $cartao->isValid(); // Isso será false para o cartão de exemplo
echo "\nVerificação simples com isValid(): " . ($eVálidoSimples ? 'Válido' : 'Inválido') . "\n";

// Exemplo com um cartão válido
$validCardNumber = '49927398716'; // Luhn válido para Visa
$validCardHolderName = 'João Santos';
$validExpirationMonth = 12; // Dezembro
$validExpirationYear = (int)date('Y') + 2; // Ano atual + 2
$validCvv = '123';

$cartaoValido = new CreditCard(
    $validCardNumber,
    $validCardHolderName,
    $validExpirationMonth,
    $validExpirationYear,
    $validCvv
);

$relatorioValido = $cartaoValido->getValidationReport();
echo "\n--- Exemplo de Cartão Válido ---\n";
echo "Geralmente Válido: " . ($relatorioValido['overall_valid'] ? 'Sim' : 'Não') . "\n";
if (empty($relatorioValido['messages'])) {
    echo "Mensagens: Nenhuma\n";
}
// isValid() para o cartão válido
echo "Verificação simples com isValid() para cartão válido: " . ($cartaoValido->isValid() ? 'Válido' : 'Inválido') . "\n";

```

## Executando os Testes

Para executar o conjunto de testes PHPUnit incluídos com a biblioteca:

```bash
./vendor/bin/phpunit
```

Alternativamente, você pode adicionar um script ao seu `composer.json`:
```json
"scripts": {
    "test": "phpunit"
}
```
E então executar:
```bash
composer test
```

## Estendendo a Biblioteca

A biblioteca é projetada para ser extensível através da `App\Validators\ValidatorInterface`.

1.  **Crie um novo Validador**:
    Implemente a `App\Validators\ValidatorInterface` e seu método `validate(CreditCard $card): array`. O método deve retornar um array com duas chaves: `'valid'` (booleano) e `'message'` (string). Por exemplo, para adicionar suporte ao American Express:
    ```php
    // src/Validators/AmexCardTypeValidator.php
    namespace App\Validators;
    use App\CreditCard;
    class AmexCardTypeValidator implements ValidatorInterface {
        public function validate(CreditCard $card): array {
            $cardNumber = preg_replace('/[^\d]/', '', $card->getCardNumber());
            // Verificações específicas de prefixo e comprimento do AMEX
            if (preg_match('/^3[47][0-9]{13}$/', $cardNumber)) {
                return ['valid' => true, 'message' => ''];
            }
            return ['valid' => false, 'message' => 'Número de cartão American Express inválido.'];
        }
    }
    ```

2.  **Integre o novo Validador**:
    Os validadores são instanciados dentro do método `CreditCard::getValidationReport()`. Para adicionar seu novo validador, você modificaria este método:
    ```php
    // Em App\CreditCard::getValidationReport()
    // ...
    $validators = [
        'luhn' => new LuhnValidator(),
        'card_type' => new CardTypeValidator(), // Atualmente, conhece apenas Visa/MC
        // 'amex_card_type' => new AmexCardTypeValidator(), // Adicione seu novo validador
        'expiration_date' => new ExpirationDateValidator(),
        'cvv' => new CvvValidator(),
        // Nota: Você pode precisar ajustar como CardTypeValidator e seu novo AmexCardTypeValidator interagem,
        // ou substituir CardTypeValidator por um despachante de tipo mais sofisticado se vários tipos de cartão forem verificados.
    ];
    // ...
    ```
    Para uma abordagem mais flexível, a classe `CreditCard` poderia ser refatorada para aceitar uma lista de validadores em seu construtor ou através de um método setter. Isso permitiria aos usuários da biblioteca personalizar a cadeia de validação sem modificar o código principal da biblioteca.

## Como Contribuir

Pull requests são bem-vindos. Para mudanças significativas, por favor, abra uma issue primeiro para discutir o que você gostaria de mudar.

Por favor, certifique-se de atualizar os testes conforme apropriado.

## Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo `composer.json` para detalhes (implicitamente, já que nenhum arquivo LICENSE separado foi gerado, mas MIT foi especificado durante o `composer init`).
```
