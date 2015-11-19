<?php namespace Bluecode\Generator\Commands;

use Exception;
use Illuminate\Console\Command;
use Bluecode\Generator\CommandData;
use Bluecode\Generator\File\FileHelper;
use Bluecode\Generator\Utils\GeneratorUtils;
use Bluecode\Generator\Utils\TableRelationshipsGenerator;
use Bluecode\Generator\Utils\TableFieldsGenerator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class BaseCommand extends Command
{
    /**
     * The command Data.
     *
     * @var CommandData
     */
    public $commandData;

    public function handle()
    {
        $this->commandData->modelName = $this->argument('model');
        $this->commandData->useSoftDelete = $this->option('softDelete');
        $this->commandData->useAuth = $this->option('auth');
        $this->commandData->fieldsFile = $this->option('fieldsFile');
        $this->commandData->paginate = $this->option('paginate');
        $this->commandData->tableName = $this->option('tableName');
        $this->commandData->skipMigration = $this->option('skipMigration');
        $this->commandData->fromTable = $this->option('fromTable');
        $this->commandData->rememberToken = $this->option('rememberToken');

        if ($this->commandData->fromTable) {
            $this->commandData->tableName = $this->commandData->fromTable;
        }

        if ($this->commandData->paginate <= 0) {
            $this->commandData->paginate = 10;
        }

        $this->commandData->initVariables();
        $this->commandData->addDynamicVariable('$NAMESPACE_APP$', $this->getLaravel()->getNamespace());

        if ($this->commandData->fieldsFile) {
            $fileHelper = new FileHelper();
            try {
                if (file_exists($this->commandData->fieldsFile)) {
                    $filePath = $this->commandData->fieldsFile;
                } else {
                    $filePath = base_path($this->commandData->fieldsFile);
                }

                if (!file_exists($filePath)) {
                    $this->commandData->commandObj->error('Fields file not found');
                    exit;
                }

                $fileContents = $fileHelper->getFileContents($filePath);
                $fields = json_decode($fileContents, true);

                $this->commandData->inputFields = GeneratorUtils::validateFieldsFile($fields);
            } catch (Exception $e) {
                $this->commandData->commandObj->error($e->getMessage());
                exit;
            }
        } elseif ($this->commandData->fromTable) {
            $tableFieldsGenerator = new TableFieldsGenerator($this->commandData->fromTable);
            $this->commandData->inputFields = $tableFieldsGenerator->generateFieldsFromTable($this->commandData->commandType);

            $tableRelationshipGenerator = new TableRelationshipsGenerator($this->commandData->fromTable);
            $this->commandData->relationships = $tableRelationshipGenerator->generateRelationshipsFromTable();
        } else {
            $this->commandData->inputFields = $this->commandData->getInputFields();
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['model', InputArgument::REQUIRED, 'Singular Model name'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    public function getOptions()
    {
        return [
            ['softDelete', null, InputOption::VALUE_NONE, 'Use Soft Delete trait'],
            ['auth', null, InputOption::VALUE_NONE, 'Use Authenticatable, Authorizable, CanResetPassword trait'],
            ['fieldsFile', null, InputOption::VALUE_REQUIRED, 'Fields input as json file'],
            ['paginate', null, InputOption::VALUE_OPTIONAL, 'Pagination for index.blade.php', 10],
            ['tableName', null, InputOption::VALUE_REQUIRED, 'Table Name'],
            ['skipMigration', null, InputOption::VALUE_NONE, 'Skip Migration generation'],
            ['fromTable', null, InputOption::VALUE_REQUIRED, 'Generate from table'],
            ['rememberToken', null, InputOption::VALUE_NONE, 'Generate rememberToken field in migration'],
        ];
    }
}
