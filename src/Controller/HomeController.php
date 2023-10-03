<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Comment;
use App\Entity\Post;
use App\Form\BookingType;
use App\Form\CommentType;
use App\Repository\SellerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\PostRepository;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/booking/{id}/{week}', name: 'app_seller_booking', defaults: ['week' => null], methods: ['GET', 'POST'])]
    public function booking(SellerRepository $sellerRepository, $id, ?int $week, EntityManagerInterface $entityManager, Request $request): Response
    {
        $seller = $sellerRepository->findOneBy(['id'=>$id]);
        $currentWeek = $week ?? date('W');
        $bookings = $seller->getBookings();

        $bookedSlots = [];

        foreach ($bookings as $booking){
            if($booking->getWeek() == $currentWeek){
                $bookedSlots[$booking->getDay()][$booking->getTime()] = true;
            }
        }

        $user = $this->getUser();

        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $day = $form->get('day')->getData();
            $time = $form->get('time')->getData();
            $booking
                ->setDay($day)
                ->setTime($time)
                ->setClient($user)
                ->setSeller($seller)
                ->setWeek($currentWeek);
            $entityManager->persist($booking);
            $entityManager->flush();
            return $this->redirectToRoute('app_seller_booking', ['id'=>$seller->getId(), 'week'=>$currentWeek]);
        }

        $duration = $seller->getDuration() ?? 30;
        $startTime = new \DateTimeImmutable('9:00');
        $endTime = new \DateTimeImmutable('18:00');

        $timeSlots = [];

        while ($startTime < $endTime){
            $timeSlots[] = $startTime->format('h:i');
            $startTime = $startTime->modify("+$duration minutes");
        }

        $weekSlots = [
            'times'=>$timeSlots,
            'days'=>['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']
        ];


        $dt = new \DateTimeImmutable();
        $dt = $dt->setISODate(date('Y'), $currentWeek);

        setlocale(LC_TIME, 'fr_FR', 'fra');

        $weekDates = [];
        for ($i = 0; $i < 5; $i++) {
            $date = new \DateTimeImmutable();
            $date = $date->setISODate((int)$date->format('o'), $currentWeek, $i + 1);
            $weekDates[] = strftime("%A %d/%m/%Y", $date->getTimestamp());
        }

        return $this->render('home/booking.html.twig', [
            'seller' => $seller,
            'bookings' => $bookings,
            'weekSlots' => $weekSlots,
            'bookedSlots' => $bookedSlots,
            'booking' => $booking,
            'form' => $form->createView(),
            'currentWeek' => $currentWeek,
            'weekDates' => $weekDates
        ]);
    }

    #[Route("/posts", name: "app_post")]
    public function post(PostRepository $postRepository): Response
    {
        return $this->render("home/post.html.twig", [
            "posts" => $postRepository->findAll(),
        ]);
    }

    #[Route("/posts/{id}-{slug}", name: "app_post_detail")]
    public function postDetail(Post $post, $id, $slug, Request $request, EntityManagerInterface $entityManager,): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setAuthor($this->getUser())
                ->setPost($post);

            $entityManager->persist($comment);
            $entityManager->flush($comment);

            return $this->redirectToRoute('app_post_detail', [
                    'id' => $id,
                    'slug' => $slug
                ]);
        }

        return $this->render("home/postDetail.html.twig", [
            "post" => $post,
            "form" => $form->createView(),
            "comment" => $comment
        ]);
    }
}