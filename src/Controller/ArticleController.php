<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArticleController extends AbstractController
{
    #[Route('/article/new', name: 'app_article_new')]
    public function newArticle(Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
        $article = new Article();
        // on crée le formulaire en liant ArticleType avec l'objet $article
        $form = $this->createForm(ArticleType::class, $article);
        // on donne accées aux données post du formulaire
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            // on récupere la saisie du champ file du formulaire
            if($form->get('file')->getData())
            {
                $file = $form->get('file')->getData();

                // slug permet de transformer une chaine de caractéres en une chaine sans espaces, accents... ex: mot clé => mot-cle
                // on crée un nouveau nom pour l'image en utilisant le slug du titre en ajoutant un uniqid ert en gardant l'extension de l'image
                $fileName = $slugger->slug($article->getTitre()) . uniqid() . '.' . $file->guessExtension();

                try{
                    // on déplace l'image dans le dossier parametré dans config/services.yaml en modifiant son nom avec $fileName
                    $file->move($this->getParameter('image_article'), $fileName);
                }catch(FileException $e)
                {
                    // gerer les exceptions en cas d'erreur lors de l'upload
                }
               
                // on affecte $fileName à la proprieté image de l'article pour l'enregistrer en bdd
                $article->setImage($fileName);
            }

                $manager->persist($article);
                $manager->flush();

            return $this->redirectToRoute('app_articles');
        }

        return $this->render("article/formulaire.html.twig" , [
            "formArticle" => $form->createView()
        ]);
    }


    #[Route('/articles', name: 'app_articles')]
    public function showAll(ArticleRepository $repo)
    {
        $articles = $repo->findBy([],['id' => 'DESC'], 2);

        return $this->render('article/lesArticles.html.twig', [
            'articles' => $articles
        ]);
    }



}
