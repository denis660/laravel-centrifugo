<?php

namespace denis660\Centrifugo\Dto;

class CentrifugoTokenDataDto
{
    public function __construct(
        public string $userId,
        public array $info = [],
        public int $expireSeconds = 0,
    ) {}
}