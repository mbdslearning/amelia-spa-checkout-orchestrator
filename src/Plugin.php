<?php
declare(strict_types=1);

namespace Amelia\SpaCheckoutOrchestrator;

use Amelia\SpaCheckoutOrchestrator\Admin\SettingsPage;
use Amelia\SpaCheckoutOrchestrator\Application\TokenPayloadFactory;
use Amelia\SpaCheckoutOrchestrator\Blocks\CheckoutLinkBlock;
use Amelia\SpaCheckoutOrchestrator\Contracts\BootableServiceInterface;
use Amelia\SpaCheckoutOrchestrator\Contracts\ServiceInterface;
use Amelia\SpaCheckoutOrchestrator\Domain\Security\TokenSigner;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Cron\ActionSchedulerBootstrap;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Logging\Logger;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\Persistence\SettingsRepository;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\WooCommerce\AmeliaRedirectAdapter;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\WooCommerce\CustomerDefaults;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\WooCommerce\Endpoint;
use Amelia\SpaCheckoutOrchestrator\Infrastructure\WooCommerce\StoreApiCheckoutHook;
use Amelia\SpaCheckoutOrchestrator\Rest\LockController;
use Amelia\SpaCheckoutOrchestrator\Support\Container;
use Amelia\SpaCheckoutOrchestrator\Support\PluginContext;

final class Plugin
{
    private Container $container;

    /** @var array<class-string<ServiceInterface>> */
    private array $services = [
        SettingsPage::class,
        AmeliaRedirectAdapter::class,
        Endpoint::class,
        StoreApiCheckoutHook::class,
        LockController::class,
        ActionSchedulerBootstrap::class,
        CheckoutLinkBlock::class,
    ];

    public function __construct()
    {
        $this->container = new Container();
        $this->registerContainerBindings();
    }

    public function register(): void
    {
        add_action('plugins_loaded', function (): void {
            $this->bootstrap();
        }, 5);
    }

    private function bootstrap(): void
    {
        // Defer until core is loaded.
        foreach ($this->services as $serviceId) {
            $service = $this->container->get($serviceId);
            if ($service instanceof ServiceInterface) {
                $service->register();
            }
        }

        foreach ($this->services as $serviceId) {
            $service = $this->container->get($serviceId);
            if ($service instanceof BootableServiceInterface) {
                $service->boot();
            }
        }
    }

    private function registerContainerBindings(): void
    {
        $this->container->set(PluginContext::class, fn () => new PluginContext());
        $this->container->set(SettingsRepository::class, fn (Container $c) => new SettingsRepository($c->get(PluginContext::class)));
        $this->container->set(TokenSigner::class, fn () => new TokenSigner());
        $this->container->set(Logger::class, fn (Container $c) => new Logger($c->get(SettingsRepository::class)));
        $this->container->set(TokenPayloadFactory::class, fn (Container $c) => new TokenPayloadFactory($c->get(SettingsRepository::class)));
        $this->container->set(CustomerDefaults::class, fn () => new CustomerDefaults());

        $this->container->set(SettingsPage::class, fn (Container $c) => new SettingsPage($c->get(SettingsRepository::class), $c->get(PluginContext::class)));
        $this->container->set(AmeliaRedirectAdapter::class, fn (Container $c) => new AmeliaRedirectAdapter($c->get(SettingsRepository::class), $c->get(TokenPayloadFactory::class), $c->get(TokenSigner::class), $c->get(Logger::class)));
        $this->container->set(Endpoint::class, fn (Container $c) => new Endpoint($c->get(SettingsRepository::class), $c->get(TokenSigner::class), $c->get(Logger::class), $c->get(PluginContext::class), $c->get(CustomerDefaults::class)));
        $this->container->set(StoreApiCheckoutHook::class, fn (Container $c) => new StoreApiCheckoutHook($c->get(TokenSigner::class), $c->get(Logger::class)));
        $this->container->set(LockController::class, fn (Container $c) => new LockController($c->get(TokenSigner::class), $c->get(SettingsRepository::class), $c->get(Logger::class)));
        $this->container->set(ActionSchedulerBootstrap::class, fn () => new ActionSchedulerBootstrap());
        $this->container->set(CheckoutLinkBlock::class, fn (Container $c) => new CheckoutLinkBlock($c->get(PluginContext::class), $c->get(SettingsRepository::class)));
    }
}
