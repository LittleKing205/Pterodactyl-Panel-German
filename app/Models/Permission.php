<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Collection;

class Permission extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'subuser_permission';

    /**
     * Constants defining different permissions available.
     */
    public const ACTION_WEBSOCKET_CONNECT = 'websocket.connect';
    public const ACTION_CONTROL_CONSOLE = 'control.console';
    public const ACTION_CONTROL_START = 'control.start';
    public const ACTION_CONTROL_STOP = 'control.stop';
    public const ACTION_CONTROL_RESTART = 'control.restart';

    public const ACTION_DATABASE_READ = 'database.read';
    public const ACTION_DATABASE_CREATE = 'database.create';
    public const ACTION_DATABASE_UPDATE = 'database.update';
    public const ACTION_DATABASE_DELETE = 'database.delete';
    public const ACTION_DATABASE_VIEW_PASSWORD = 'database.view_password';

    public const ACTION_SCHEDULE_READ = 'schedule.read';
    public const ACTION_SCHEDULE_CREATE = 'schedule.create';
    public const ACTION_SCHEDULE_UPDATE = 'schedule.update';
    public const ACTION_SCHEDULE_DELETE = 'schedule.delete';

    public const ACTION_USER_READ = 'user.read';
    public const ACTION_USER_CREATE = 'user.create';
    public const ACTION_USER_UPDATE = 'user.update';
    public const ACTION_USER_DELETE = 'user.delete';

    public const ACTION_BACKUP_READ = 'backup.read';
    public const ACTION_BACKUP_CREATE = 'backup.create';
    public const ACTION_BACKUP_DELETE = 'backup.delete';
    public const ACTION_BACKUP_DOWNLOAD = 'backup.download';
    public const ACTION_BACKUP_RESTORE = 'backup.restore';

    public const ACTION_ALLOCATION_READ = 'allocation.read';
    public const ACTION_ALLOCATION_CREATE = 'allocation.create';
    public const ACTION_ALLOCATION_UPDATE = 'allocation.update';
    public const ACTION_ALLOCATION_DELETE = 'allocation.delete';

    public const ACTION_FILE_READ = 'file.read';
    public const ACTION_FILE_READ_CONTENT = 'file.read-content';
    public const ACTION_FILE_CREATE = 'file.create';
    public const ACTION_FILE_UPDATE = 'file.update';
    public const ACTION_FILE_DELETE = 'file.delete';
    public const ACTION_FILE_ARCHIVE = 'file.archive';
    public const ACTION_FILE_SFTP = 'file.sftp';

    public const ACTION_STARTUP_READ = 'startup.read';
    public const ACTION_STARTUP_UPDATE = 'startup.update';
    public const ACTION_STARTUP_DOCKER_IMAGE = 'startup.docker-image';

    public const ACTION_SETTINGS_RENAME = 'settings.rename';
    public const ACTION_SETTINGS_REINSTALL = 'settings.reinstall';

    /**
     * Should timestamps be used on this model.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'subuser_id' => 'integer',
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'subuser_id' => 'required|numeric|min:1',
        'permission' => 'required|string',
    ];

    /**
     * All of the permissions available on the system. You should use self::permissions()
     * to retrieve them, and not directly access this array as it is subject to change.
     *
     * @var array
     *
     * @see \Pterodactyl\Models\Permission::permissions()
     */
    protected static $permissions = [
        'websocket' => [
            'description' => 'Allows the user to connect to the server websocket, giving them access to view console output and realtime server stats.',
            'keys' => [
                'connect' => 'Allows a user to connect to the websocket instance for a server to stream the console.',
            ],
        ],

        'control' => [
            'description' => 'Berechtigungen, die die Fähigkeit eines Benutzers steuern, den Energiezustand eines Servers zu steuern oder Befehle zu senden.',
            'keys' => [
                'console' => 'Ermöglicht einem Benutzer das Senden von Befehlen an die Serverinstanz über die Konsole.',
                'start' => 'Ermöglicht einem Benutzer, den Server zu starten, wenn er gestoppt ist.',
                'stop' => 'Ermöglicht einem Benutzer, einen Server zu stoppen, wenn er gestartet ist.',
                'restart' => 'Ermöglicht einem Benutzer, einen Serverneustart durchzuführen. Dadurch können sie den Server starten, wenn er offline ist, aber den Server nicht in einen vollständig angehaltenen Zustand versetzen.',
            ],
        ],

        'user' => [
            'description' => 'Berechtigungen, die es einem Benutzer ermöglichen, andere Unterbenutzer auf einem Server zu verwalten. Sie werden nie in der Lage sein, ihr eigenes Konto zu bearbeiten oder Berechtigungen zuzuweisen, die sie selbst nicht haben.',
            'keys' => [
                'create' => 'Ermöglicht einem Benutzer, neue Unterbenutzer für den Server zu erstellen.',
                'read' => 'Ermöglicht dem Benutzer, Unterbenutzer und ihre Berechtigungen für den Server anzuzeigen.',
                'update' => 'Ermöglicht einem Benutzer, andere Unterbenutzer zu ändern.',
                'delete' => 'Ermöglicht einem Benutzer, einen Unterbenutzer vom Server zu löschen.',
            ],
        ],

        'file' => [
            'description' => 'Berechtigungen, die die Fähigkeit eines Benutzers steuern, das Dateisystem für diesen Server zu ändern.',
            'keys' => [
                'create' => 'Ermöglicht einem Benutzer das Erstellen zusätzlicher Dateien und Ordner über das Panel oder den direkten Upload.',
                'read' => 'Ermöglicht einem Benutzer, den Inhalt eines Verzeichnisses anzuzeigen, aber nicht den Inhalt von Dateien anzuzeigen oder Dateien herunterzuladen.',
                'read-content' => 'Ermöglicht einem Benutzer, den Inhalt einer bestimmten Datei anzuzeigen. Dadurch kann der Benutzer auch Dateien herunterladen.',
                'update' => 'Ermöglicht einem Benutzer, den Inhalt einer vorhandenen Datei oder eines Verzeichnisses zu aktualisieren.',
                'delete' => 'Ermöglicht einem Benutzer, Dateien oder Verzeichnisse zu löschen.',
                'archive' => 'Ermöglicht einem Benutzer, den Inhalt eines Verzeichnisses zu archivieren sowie vorhandene Archive auf dem System zu dekomprimieren.',
                'sftp' => 'Ermöglicht einem Benutzer, sich mit SFTP zu verbinden und Serverdateien mit den anderen zugewiesenen Dateiberechtigungen zu verwalten.',
            ],
        ],

        'backup' => [
            'description' => 'Berechtigungen, die die Fähigkeit eines Benutzers steuern, Serversicherungen zu erstellen und zu verwalten.',
            'keys' => [
                'create' => 'Ermöglicht einem Benutzer, neue Sicherungen für diesen Server zu erstellen.',
                'read' => 'Ermöglicht einem Benutzer, alle Sicherungen anzuzeigen, die für diesen Server vorhanden sind.',
                'delete' => 'Ermöglicht einem Benutzer, Backups aus dem System zu entfernen.',
                'download' => 'Ermöglicht einem Benutzer, eine Sicherung für den Server herunterzuladen. Achtung: Dadurch kann ein Benutzer auf alle Dateien des Servers im Backup zugreifen.',
                'restore' => 'Ermöglicht einem Benutzer, eine Sicherung für den Server wiederherzustellen. Achtung: Dadurch kann der Benutzer alle Serverdateien im Prozess löschen.',
            ],
        ],

        // Controls permissions for editing or viewing a server's allocations.
        'allocation' => [
            'description' => 'Berechtigungen, die die Fähigkeit eines Benutzers steuern, die Portzuweisungen für diesen Server zu ändern.',
            'keys' => [
                'read' => 'Ermöglicht einem Benutzer, alle Zuordnungen anzuzeigen, die diesem Server derzeit zugewiesen sind. Benutzer mit beliebiger Zugriffsebene auf diesen Server können immer die primäre Zuordnung anzeigen.',
                'create' => 'Ermöglicht einem Benutzer, dem Server zusätzliche Zuordnungen zuzuweisen.',
                'update' => 'Ermöglicht einem Benutzer das Ändern der primären Serverzuweisung und das Anhängen von Notizen an jede Zuweisung.',
                'delete' => 'Ermöglicht einem Benutzer, eine Zuordnung vom Server zu löschen.',
            ],
        ],

        // Controls permissions for editing or viewing a server's startup parameters.
        'startup' => [
            'description' => 'Berechtigungen, die die Fähigkeit eines Benutzers steuern, die Startparameter dieses Servers anzuzeigen.',
            'keys' => [
                'read' => 'Ermöglicht einem Benutzer, die Startvariablen für einen Server anzuzeigen.',
                //'read' => 'Na Sirega hast du wirklich alles gelesen?',
                'update' => 'Ermöglicht einem Benutzer, die Startvariablen für den Server zu ändern.',
                'docker-image' => 'Ermöglicht einem Benutzer, das beim Ausführen des Servers verwendete Docker-Image zu ändern.',
            ],
        ],

        'database' => [
            'description' => 'Berechtigungen, die den Zugriff eines Benutzers auf die Datenbankverwaltung für diesen Server steuern.',
            'keys' => [
                'create' => 'Ermöglicht einem Benutzer, eine neue Datenbank für diesen Server zu erstellen.',
                'read' => 'Ermöglicht einem Benutzer, die diesem Server zugeordnete Datenbank anzuzeigen.',
                'update' => 'Ermöglicht einem Benutzer, das Kennwort für eine Datenbankinstanz zu rotieren. Wenn der Benutzer nicht über die Berechtigung view_password verfügt, wird ihm das aktualisierte Passwort nicht angezeigt.',
                'delete' => 'Ermöglicht einem Benutzer, eine Datenbankinstanz von diesem Server zu entfernen.',
                'view_password' => 'Ermöglicht einem Benutzer, das einer Datenbankinstanz für diesen Server zugeordnete Kennwort anzuzeigen.',
            ],
        ],

        'subdomain' => [
            'description' => 'Subdomain verwalten',
            'keys' => [
                'manage' => 'Subdomain für aktuellen Server erstellen / löschen.',
            ],
        ],

        'schedule' => [
            'description' => 'Berechtigungen, die den Zugriff eines Benutzers auf die Zeitplanverwaltung für diesen Server steuern.',
            'keys' => [
                'create' => 'Ermöglicht einem Benutzer, neue Zeitpläne für diesen Server zu erstellen.', // task.create-schedule
                'read' => 'Ermöglicht einem Benutzer, Zeitpläne und die damit verbundenen Aufgaben für diesen Server anzuzeigen.', // task.view-schedule, task.list-schedules
                'update' => 'Ermöglicht einem Benutzer, Zeitpläne zu aktualisieren und Aufgaben für diesen Server zu planen.', // task.edit-schedule, task.queue-schedule, task.toggle-schedule
                'delete' => 'Ermöglicht einem Benutzer, Zeitpläne für diesen Server zu löschen.', // task.delete-schedule
            ],
        ],

        'settings' => [
            'description' => 'Berechtigungen, die den Zugriff eines Benutzers auf die Einstellungen für diesen Server steuern.',
            'keys' => [
                'rename' => 'Ermöglicht einem Benutzer, diesen Server umzubenennen.',
                'reinstall' => 'Ermöglicht einem Benutzer, eine Neuinstallation dieses Servers auszulösen.',
            ],
        ],
    ];

    /**
     * Returns all of the permissions available on the system for a user to
     * have when controlling a server.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function permissions(): Collection
    {
        return Collection::make(self::$permissions);
    }
}
