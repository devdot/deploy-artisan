<?php

namespace Devdot\DeployArtisan\Models;

enum Role
{
    case Server;
    case Client;
    case Undefined;
}
