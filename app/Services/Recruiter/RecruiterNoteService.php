<?php

namespace App\Services\Recruiter;

use App\Models\Application;
use App\Models\Employer;
use App\Models\RecruiterNote;

class RecruiterNoteService
{
    public function create(Employer $employer, Application $application, string $body): RecruiterNote
    {
        return RecruiterNote::query()->create([
            'employer_id' => $employer->id,
            'application_id' => $application->id,
            'body' => $body,
        ]);
    }

    public function update(RecruiterNote $note, string $body): RecruiterNote
    {
        $note->update(['body' => $body]);

        return $note->fresh();
    }

    public function delete(RecruiterNote $note): void
    {
        $note->delete();
    }
}
