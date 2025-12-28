<?php

namespace App\Filament\Resources\RegisterTeamResource\Pages;

use App\Filament\Resources\RegisterTeamResource;
use Filament\Resources\Pages\Page;

class RegisterTeam extends Page
{
    protected static string $resource = RegisterTeamResource::class;

    protected static string $view = 'filament.resources.register-team-resource.pages.register-team';
}
