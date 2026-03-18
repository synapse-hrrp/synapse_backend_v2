<?php

namespace Modules\Pharmacie\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StockPeremptionProche extends Notification
{
    use Queueable;

    protected $stocks;

    public function __construct($stocks)
    {
        $this->stocks = $stocks;
    }

    /**
     * Canaux de notification
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Email de notification
     */
    public function toMail($notifiable): MailMessage
    {
        $count = $this->stocks->count();
        
        $message = (new MailMessage)
            ->subject('⚠️ Alerte Péremption - ' . $count . ' produit(s) proche(s)')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('**' . $count . ' lot(s)** de produits arrivent à expiration dans les 30 prochains jours.')
            ->line('**Action requise** : Vérifiez et écoulez ces stocks rapidement.');

        // Ajouter la liste des stocks
        $message->line('---');
        $message->line('**Liste des produits concernés :**');
        
        foreach ($this->stocks->take(10) as $stock) {
            $joursRestants = now()->diffInDays($stock->date_peremption, false);
            
            $message->line(sprintf(
                '• **%s** - Lot: %s - Qté: %d - Expire le: %s (%d jours)',
                $stock->produit->nom,
                $stock->numero_lot,
                $stock->quantite,
                \Carbon\Carbon::parse($stock->date_peremption)->format('d/m/Y'),
                $joursRestants
            ));
        }

        if ($this->stocks->count() > 10) {
            $message->line('... et ' . ($this->stocks->count() - 10) . ' autre(s)');
        }

        $message->action('Voir les alertes', url('/api/pharmacie/stocks/proches'))
                ->line('Merci de traiter ces alertes rapidement.')
                ->salutation('Cordialement, Le système de gestion Pharmacie');

        return $message;
    }
}