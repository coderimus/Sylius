<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\Sylius\Bundle\ApiBundle\CommandHandler;

use Doctrine\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Bundle\ApiBundle\Command\Cart\PickupCart;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Generator\RandomnessGeneratorInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class PickupCartHandlerSpec extends ObjectBehavior
{
    function let(
        FactoryInterface $cartFactory,
        OrderRepositoryInterface $cartRepository,
        ChannelRepositoryInterface $channelRepository,
        UserContextInterface $userContext,
        ObjectManager $orderManager,
        RandomnessGeneratorInterface $generator
    ): void {
        $this->beConstructedWith(
            $cartFactory,
            $cartRepository,
            $channelRepository,
            $userContext,
            $orderManager,
            $generator
        );
    }

    function it_is_a_message_handler(): void
    {
        $this->shouldImplement(MessageHandlerInterface::class);
    }

    function it_picks_up_a_new_cart_for_logged_in_shop_user(
        FactoryInterface $cartFactory,
        OrderRepositoryInterface $cartRepository,
        ChannelRepositoryInterface $channelRepository,
        UserContextInterface $userContext,
        ShopUserInterface $user,
        CustomerInterface $customer,
        ObjectManager $orderManager,
        RandomnessGeneratorInterface $generator,
        OrderInterface $cart,
        ChannelInterface $channel,
        CurrencyInterface $currency,
        LocaleInterface $locale
    ): void {
        $pickupCart = new PickupCart();
        $pickupCart->setChannelCode('code');

        $channelRepository->findOneByCode('code')->willReturn($channel);
        $channel->getBaseCurrency()->willReturn($currency);
        $channel->getDefaultLocale()->willReturn($locale);

        $userContext->getUser()->willReturn($user);
        $user->getCustomer()->willReturn($customer);

        $cartRepository->findLatestNotEmptyCartByChannelAndCustomer($channel, $customer)->willReturn(null);

        $generator->generateUriSafeString(10)->willReturn('urisafestr');
        $currency->getCode()->willReturn('USD');
        $locale->getCode()->willReturn('en_US');

        $cartFactory->createNew()->willReturn($cart);
        $cart->setCustomer($customer)->shouldBeCalled();
        $cart->setChannel($channel)->shouldBeCalled();
        $cart->setCurrencyCode('USD')->shouldBeCalled();
        $cart->setLocaleCode('en_US')->shouldBeCalled();
        $cart->setTokenValue('urisafestr')->shouldBeCalled();

        $orderManager->persist($cart)->shouldBeCalled();

        $this($pickupCart);
    }

    function it_picks_up_an_existing_cart_for_logged_in_shop_user(
        FactoryInterface $cartFactory,
        OrderRepositoryInterface $cartRepository,
        ChannelRepositoryInterface $channelRepository,
        UserContextInterface $userContext,
        ShopUserInterface $user,
        CustomerInterface $customer,
        ObjectManager $orderManager,
        OrderInterface $cart,
        ChannelInterface $channel
    ): void {
        $pickupCart = new PickupCart();
        $pickupCart->setChannelCode('code');

        $channelRepository->findOneByCode('code')->willReturn($channel);

        $userContext->getUser()->willReturn($user);
        $user->getCustomer()->willReturn($customer);

        $cartRepository->findLatestNotEmptyCartByChannelAndCustomer($channel, $customer)->willReturn($cart);

        $cartFactory->createNew()->willReturn($cart);
        $cart->setCustomer($customer)->shouldNotBeCalled();
        $cart->setChannel($channel)->shouldNotBeCalled();

        $orderManager->persist($cart)->shouldNotBeCalled();

        $this($pickupCart);
    }

    function it_picks_up_a_cart_for_visitor(
        FactoryInterface $cartFactory,
        OrderRepositoryInterface $cartRepository,
        ChannelRepositoryInterface $channelRepository,
        UserContextInterface $userContext,
        ObjectManager $orderManager,
        RandomnessGeneratorInterface $generator,
        OrderInterface $cart,
        ChannelInterface $channel,
        CurrencyInterface $currency,
        LocaleInterface $locale
    ): void {
        $pickupCart = new PickupCart();
        $pickupCart->setChannelCode('code');

        $channelRepository->findOneByCode('code')->willReturn($channel);
        $channel->getBaseCurrency()->willReturn($currency);
        $channel->getDefaultLocale()->willReturn($locale);

        $userContext->getUser()->willReturn(null);

        $cartRepository->findLatestNotEmptyCartByChannelAndCustomer($channel, Argument::any())->shouldNotBeCalled(null);

        $generator->generateUriSafeString(10)->willReturn('urisafestr');
        $currency->getCode()->willReturn('USD');
        $locale->getCode()->willReturn('en_US');

        $cartFactory->createNew()->willReturn($cart);
        $cart->setCustomer(Argument::any())->shouldNotBeCalled();
        $cart->setChannel($channel)->shouldBeCalled();
        $cart->setCurrencyCode('USD')->shouldBeCalled();
        $cart->setLocaleCode('en_US')->shouldBeCalled();
        $cart->setTokenValue('urisafestr')->shouldBeCalled();

        $orderManager->persist($cart)->shouldBeCalled();

        $this($pickupCart);
    }
}
