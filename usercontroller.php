<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    /**
     * @Route("/api/upload", methods={"POST"})
     */
    public function upload(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $file = $request->files->get('csv_file');
        if (!$file || !$file->isValid()) {
            return new Response('Invalid file', Response::HTTP_BAD_REQUEST);
        }

        // Open and parse the CSV file
        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $user = new User();
                $user->setName($data[0]);
                $user->setEmail($data[1]);
                $user->setUsername($data[2]);
                $user->setAddress($data[3]);
                $user->setRole($data[4]);

                $em->persist($user);

                // Send email asynchronously
                $email = (new Email())
                    ->from('no-reply@example.com')
                    ->to($user->getEmail())
                    ->subject('Data Storage Successful')
                    ->text('Your data has been successfully stored.');
                
                $mailer->send($email);
            }
            fclose($handle);
            $em->flush();
        }

        return new Response('Data uploaded and saved successfully', Response::HTTP_OK);
    }

    /**
     * @Route("/api/users", methods={"GET"})
     */
    public function viewUsers(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        return $this->json($users);
    }
}
