<?php

// Check existing users
$users = DB::table('users')->select('id', 'name', 'username')->get();

$usernames = [];
foreach ($users as $user) {
    if ($user->username) {
        $usernames[$user->username] = $user->id;
    } else {
        $baseUsername = strtolower(str_replace(' ', '', $user->name));
        $username = $baseUsername;
        $counter = 1;
        
        while (isset($usernames[$username])) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        $usernames[$username] = $user->id;
    }
}

echo "Username assignment completed.\n";