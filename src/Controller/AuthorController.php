<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Author;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AuthorController extends AbstractController
{
    /**
     * @Route("/author", name="author_index", methods={"GET"})
     */
    public function index(Request $r): Response
    {

        // $authors = $this->getDoctrine()
        //     ->getRepository(Author::class)
        //     ->findAll();

        $authors = $this->getDoctrine()
            ->getRepository(Author::class);

        if ('name_az' == $r->query->get('sort')) {
            $authors = $authors->findBy([], ['name' => 'asc']);
        } elseif ('name_za' == $r->query->get('sort')) {
            $authors = $authors->findBy([], ['name' => 'desc']);
        } elseif ('surname_az' == $r->query->get('sort')) {
            $authors = $authors->findBy([], ['surname' => 'asc']);
        } elseif ('surname_az' == $r->query->get('sort')) {
            $authors = $authors->findBy([], ['surname' => 'desc']);
        } else {
            $authors =  $authors->findAll();
        }

        return $this->render('author/index.html.twig', [
            'authors' => $authors,
            'sortBy' => $r->query->get('sort') ?? 'default',
            'success' => $r->getSession()->getFlashBag()->get('success', []),
            'errors' => $r->getSession()->getFlashBag()->get('errors', [])
        ]);
    }

    /**
     * @Route("/author/create", name="author_create", methods={"GET"})
     */
    public function create(Request $r): Response
    {

        $author_name = $r->getSession()->getFlashBag()->get('author_name', []);
        $author_surname = $r->getSession()->getFlashBag()->get('author_surname', []);

        return $this->render('author/create.html.twig', [
            'errors' => $r->getSession()->getFlashBag()->get('errors', []),
            'author_name' => $author_name[0] ?? '',
            'author_surname' => $author_surname[0] ?? ''
        ]);
    }

    /**
     * @Route("/author/store", name="author_store", methods={"POST"})
     */
    public function store(Request $r, ValidatorInterface $validator): Response
    {

        $submittedToken = $r->request->get('token');

        if (!$this->isCsrfTokenValid('create_author_bla', $submittedToken)) {
            $r->getSession()->getFlashBag()->add('errors', 'Blogas Tokenas CSRF');
            return $this->redirectToRoute('author_create');
        }


        $author = new Author;
        $author->setName($r->request->get('author_name'))->setSurname($r->request->get('author_surname'));

        $errors = $validator->validate($author);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $r->getSession()->getFlashBag()->add('errors', $error->getMessage());
            }
            $r->getSession()->getFlashBag()->add('author_name', $r->request->get('author_name'));
            $r->getSession()->getFlashBag()->add('author_surname', $r->request->get('author_surname'));
            return $this->redirectToRoute('author_create');
        }



        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($author);
        $entityManager->flush();

        $r->getSession()->getFlashBag()->add('success', 'Autorius sėkmingai buvo pridėtas.');

        return $this->redirectToRoute('author_index');
    }

    /**
     * @Route("/author/edit/{id}", name="author_edit", methods={"GET"})
     */
    public function edit(int $id, Request $r): Response
    {
        $author = $this->getDoctrine()
            ->getRepository(Author::class)
            ->find($id);

        $author_name = $r->getSession()->getFlashBag()->get('author_name', []);
        $author_surname = $r->getSession()->getFlashBag()->get('author_surname', []);


        return $this->render('author/edit.html.twig', [
            'author' => $author,
            'errors' => $r->getSession()->getFlashBag()->get('errors', []),
            'author_name' => $author_name[0] ?? '',
            'author_surname' => $author_surname[0] ?? ''
        ]);
    }

    /**
     * @Route("/author/update/{id}", name="author_update", methods={"POST"})
     */
    public function update(Request $r, $id, ValidatorInterface $validator): Response
    {
        $author = $this->getDoctrine()
            ->getRepository(Author::class)
            ->find($id);

        $author->setName($r->request->get('author_name'))->setSurname($r->request->get('author_surname'));


        $errors = $validator->validate($author);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $r->getSession()->getFlashBag()->add('errors', $error->getMessage());
            }
            $r->getSession()->getFlashBag()->add('author_name', $r->request->get('author_name'));
            $r->getSession()->getFlashBag()->add('author_surname', $r->request->get('author_surname'));

            return $this->redirectToRoute('author_edit', ['id' => $author->getId()]);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($author);
        $entityManager->flush();

        $r->getSession()->getFlashBag()->add('success', 'Autorius sėkmingai buvo paredaguotas.');

        return $this->redirectToRoute('author_index');
    }

    /**
     * @Route("/author/delete/{id}", name="author_delete", methods={"POST"})
     */
    public function delete(ValidatorInterface $validator, Request $r, $id): Response
    {
        $author = $this->getDoctrine()
            ->getRepository(Author::class)
            ->find($id);

        //dd($author->getBooks()->count());

        // if ($author->getBooks()->count() > 0) {
        //     return new Response('Trinti negalima nes turi knygų');
        // }
        $errors = $validator->validate($author);

        if ($author->getBooks()->count() > 0) { 
        $r->getSession()->getFlashBag()->add('errors', 'Trinti negalima, nes turi knygų');
        return $this->redirectToRoute('author_index');
    }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($author);
        $entityManager->flush();


        $r->getSession()->getFlashBag()->add('success', 'Autorius buvo sėkmingai ištrintas.');

        return $this->redirectToRoute('author_index');
    }
}
