<?php namespace Winter\User\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Models\User;

class UserList extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'User List',
            'description' => 'Displays a list of users.'
        ];
    }

    public function onRun()
    {
        $this->page['users'] = $this->loadUsers();
    }

    protected function loadUsers()
    {
        return User::all();
    }
}
