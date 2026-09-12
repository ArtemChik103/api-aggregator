<?php

namespace App\Console\Commands;

use App\Models\ApiProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ProviderCreateCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'provider:create
                            {--name= : Уникальное имя провайдера}
                            {--base_url= : Базовый URL API провайдера}
                            {--description= : Описание провайдера}
                            {--status= : Статус провайдера (active или inactive)}
                            {--credentials= : Учетные данные в формате JSON}';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Интерактивное создание нового API-провайдера в базе данных';

    /**
     * Выполнение команды.
     */
    public function handle(): int
    {
        $this->info('=== Добавление нового API-провайдера ===');

        $name = $this->option('name');
        if (empty($name)) {
            do {
                $name = $this->ask('Введите уникальное имя провайдера (например, open-meteo)');
                if (empty($name)) {
                    $this->error('Имя провайдера обязательно для заполнения.');
                    continue;
                }
                if (ApiProvider::where('name', $name)->exists()) {
                    $this->error("Провайдер с именем '{$name}' уже существует.");
                    $name = null;
                }
            } while (empty($name));
        }

        $baseUrl = $this->option('base_url');
        if (empty($baseUrl)) {
            do {
                $baseUrl = $this->ask('Введите базовый URL API (например, https://api.open-meteo.com/v1)');
                if (empty($baseUrl) || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
                    $this->error('Пожалуйста, укажите корректный URL (например, https://api.open-meteo.com/v1).');
                    $baseUrl = null;
                }
            } while (empty($baseUrl));
        }

        $description = $this->option('description');
        if ($description === null && $this->input->isInteractive()) {
            $description = $this->ask('Введите описание провайдера (опционально)', null);
        }

        $status = $this->option('status');
        if (empty($status)) {
            if ($this->input->isInteractive()) {
                $status = $this->choice('Выберите статус провайдера', ['active', 'inactive'], 0);
            } else {
                $status = 'active';
            }
        }

        $credentialsInput = $this->option('credentials');
        if ($credentialsInput === null && $this->input->isInteractive()) {
            $credentialsInput = $this->ask('Введите учетные данные в формате JSON (опционально, либо Enter для пропуска)', null);
        }

        $credentials = null;
        if (!empty($credentialsInput)) {
            $decoded = json_decode($credentialsInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Некорректный формат JSON для credentials.');
                return self::FAILURE;
            }
            $credentials = $decoded;
        }

        $validator = Validator::make([
            'name' => $name,
            'base_url' => $baseUrl,
            'description' => $description,
            'status' => $status,
            'credentials' => $credentials,
        ], [
            'name' => 'required|string|max:255|unique:api_providers,name',
            'base_url' => 'required|url|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'credentials' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            $this->error('Ошибки валидации:');
            foreach ($validator->errors()->all() as $error) {
                $this->error(" - {$error}");
            }
            return self::FAILURE;
        }

        $provider = ApiProvider::create([
            'name' => $name,
            'base_url' => $baseUrl,
            'description' => $description,
            'status' => $status,
            'credentials' => $credentials,
        ]);

        $this->info("Провайдер '{$provider->name}' успешно создан!");

        $this->table(
            ['ID', 'Имя', 'Базовый URL', 'Статус', 'Описание'],
            [[
                $provider->id,
                $provider->name,
                $provider->base_url,
                $provider->status,
                $provider->description ?? '—',
            ]]
        );

        return self::SUCCESS;
    }
}
