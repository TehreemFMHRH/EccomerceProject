<?php

namespace Webkul\Core\Providers;

use Dotenv\Exception\InvalidFileException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput;

class EnvValidatorServiceProvider extends ServiceProvider
{
    
    protected $rules = [
        'DB_PREFIX' => 'not_regex:/[^A-Za-z0-9_]/',
    ];

    
    protected $messages = [
        'not_regex' => 'DB_PREFIX ENV is not valid.',
    ];

    
    public function boot()
    {
        $this->validateEnvVariables();
    }

    
    private function validateEnvVariables()
    {
        $validator = Validator::make($_ENV, $this->rules, $this->messages);

        if ($validator->fails()) {
            $errorKey = collect($validator->errors()->keys())->first();
            $errorValue = env($errorKey);

            $this->writeErrorAndDie(new InvalidFileException(
                $this->getErrorMessage('some invalid values', $errorValue)
            ));
        }
    }

    
    private function getErrorMessage($cause, $subject)
    {
        return sprintf(
            'Failed to parse dotenv file due to %s. Failed at [%s].',
            $cause,
            strtok($subject, "\n")
        );
    }

    
    private function writeErrorAndDie(InvalidFileException $e)
    {
        $output = (new ConsoleOutput)->getErrorOutput();

        $output->writeln('The environment file is invalid!');
        $output->writeln($e->getMessage());

        exit(1);
    }
}
