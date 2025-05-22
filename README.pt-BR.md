# Biblioteca PHP para Validação de Cartão de Crédito

## Visão Geral

Este projeto fornece uma biblioteca PHP para validar detalhes de cartões de crédito. Ele foca em diversos aspectos chave da validação: o algoritmo de Luhn para integridade do número do cartão, identificação do tipo de cartão (Visa e Mastercard), verificação da data de expiração e validação do CVV. A biblioteca é projetada com uma abordagem modular, utilizando o padrão Strategy para seus validadores, tornando-a extensível.

Tipos de cartão atualmente suportados:
*   Visa
*   Mastercard

## Funcionalidades

*   **Verificação do Algoritmo de Luhn**: Valida os números dos cartões contra o algoritmo de Luhn (Mod 10).
*   **Identificação do Tipo de Cartão**: Identifica e valida números de cartão pertencentes a Visa e Mastercard.
*   **Validação da Data de Expiração**: Garante que a data de expiração do cartão não passou e que o mês/ano são válidos. Os cartões são considerados válidos durante todo o mês de expiração.
*   **Validação do CVV**: Valida o CVV (Código de Verificação do Cartão). Para Visa e Mastercard, um CVV de 3 dígitos é esperado.
*   **Design Extensível**: Utiliza o padrão Strategy (`ValidatorInterface`), permitindo que novas regras de validação (e.g., para outros tipos de cartão ou verificações personalizadas) sejam adicionadas facilmente.

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

Aqui está um exemplo básico de como usar a biblioteca para validar um cartão de crédito:

```php
<?php

require 'vendor/autoload.php'; // Se instalado via Composer

use App\CreditCard;

// Exemplo de detalhes (substitua com dados reais do cartão para testes)
// Nota: Usando um número Visa válido conhecido para demonstração (Luhn).
$cardNumber = '49927398716'; // Visa válido (Luhn)
$cardHolderName = 'Seu Nome';    // Nome do titular
$expirationMonth = 12;             // Mês de expiração (ex: 12 para Dezembro)
$expirationYear = (int)date('Y') + 2; // Ano de expiração (ex: ano atual + 2)
$cvv = '123';                   // CVV

$cartao = new CreditCard(
    $cardNumber,
    $cardHolderName,
    $expirationMonth,
    $expirationYear,
    $cvv
);

if ($cartao->isValid()) {
    echo "Cartão de crédito é válido.\n";
} else {
    echo "Cartão de crédito é inválido.\n";
    // Você pode querer relatórios de erro mais detalhados em uma aplicação real
    // verificando cada validador ou fazendo com que isValid() retorne um array de erros.
}

// Exemplo de um cartão inválido
$cartaoInvalido = new CreditCard(
    '49927398717', // Luhn inválido
    'Outro Nome',
    1, // Janeiro
    (int)date('Y') - 1, // Expirado ano passado
    '12' // CVV inválido
);

if ($cartaoInvalido->isValid()) {
    echo "Cartão inválido de alguma forma passou na validação (isso não deveria acontecer).\n";
} else {
    echo "Cartão inválido corretamente reportado como inválido.\n";
}
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
    Implemente a `App\Validators\ValidatorInterface` e seu método `validate(CreditCard $card): bool`. Por exemplo, para adicionar suporte ao American Express:
    ```php
    // src/Validators/AmexCardTypeValidator.php
    namespace App\Validators;
    use App\CreditCard;
    class AmexCardTypeValidator implements ValidatorInterface {
        public function validate(CreditCard $card): bool {
            // Verificações específicas de prefixo e comprimento do AMEX
            $cardNumber = preg_replace('/[^\d]/', '', $card->getCardNumber());
            if (preg_match('/^3[47][0-9]{13}$/', $cardNumber)) {
                return true;
            }
            return false;
        }
    }
    ```

2.  **Integre o novo Validador**:
    Atualmente, os validadores são codificados diretamente no método `CreditCard::isValid()`. Para adicionar seu novo validador, você modificaria este método:
    ```php
    // Em App\CreditCard::isValid()
    // ...
    $validators = [
        new LuhnValidator(),
        new CardTypeValidator(), // Atualmente, conhece apenas Visa/MC
        // new AmexCardTypeValidator(), // Adicione seu novo validador
        new ExpirationDateValidator(),
        new CvvValidator(), // Também pode precisar de ajuste para Amex (CVV de 4 dígitos)
    ];
    // ...
    ```
    Para uma abordagem mais flexível, a classe `CreditCard` poderia ser refatorada para aceitar uma lista de validadores em seu construtor ou através de um método setter. Isso permitiria aos usuários da biblioteca personalizar a cadeia de validação sem modificar o código principal da biblioteca. Por exemplo, `CardTypeValidator` poderia ser modificado para receber uma lista de padrões de tipos de cartão suportados.

## Como Contribuir

Pull requests são bem-vindos. Para mudanças significativas, por favor, abra uma issue primeiro para discutir o que você gostaria de mudar.

Por favor, certifique-se de atualizar os testes conforme apropriado.

## Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo `composer.json` para detalhes (implicitamente, já que nenhum arquivo LICENSE separado foi gerado, mas MIT foi especificado durante o `composer init`).
```
