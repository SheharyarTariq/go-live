<?php

declare(strict_types=1);

namespace App\ApiResource\DTO;

use Symfony\Component\Serializer\Annotation as Serializer;

final class UserOutput
{
    #[Serializer\Groups(['User:V$Register'])]
    public string $id;

    #[Serializer\Groups(['User:V$Register'])]
    public string $email;

    #[Serializer\Groups(['User:V$Register'])]
    public string $name;
  

}