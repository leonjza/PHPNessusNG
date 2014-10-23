<?php

include '../vendor/autoload.php';

// Prepare the connection to the API
$nessus = new Nessus\Client('username', 'password', '192.168.56.101');

// Get the configured users...
// GET /users
$users = $nessus->users()->via('get')->users;

// ... and print some information
foreach ($users as $user)
    print '[+] id:' . $user->id . " - " . $user->type . ' user ' . $user->username . ' last login: ' . $user->lastlogin . PHP_EOL;

// Create a new user
// POST /users
$new_user = $nessus->users()
                ->setFields(
                    array(
                        'username' => 'apiuser',
                        'password' => 'apiuser',
                        'permissions' => 128,   // Full permissions
                        'name' => 'API User',
                        'email' => 'api@hostname.local',
                        'type' => 'local'
                    )
                )
                ->via('post');
print '[+] Created new user ' . $new_user->name . ' with id ' . $new_user->id . PHP_EOL;

// Edit the user
// PUT /users/{user_id}
//This API call appears to be broken?
$user_edit = $nessus->users($new_user->id)
                ->setFields(
                    array(
                        'permissions' => 128,
                        'name' => 'Edited API Name',
                        'email' => 'apiedit@hostname.local'
                    )
                )
                ->via('put');
print '[+] Edited user ' . $new_user->id . PHP_EOL;

// Delete the user
// DELETE /users/{user_id}
$deleted_user = $nessus->users($new_user->id)->via('delete');
print '[+] Deleted user ' . $new_user->id . PHP_EOL;

// λ git n6* → php users.php
// [+] id:3 - local user test last login: 1413804979
// [+] id:4 - local user username last login: 1413876143
// [+] Created new user apiuser with id 27
// [+] Edited user 27
// [+] Deleted user 27
