import { Head } from "@inertiajs/react";
import Navbar from "./components/Navbar";
import Hero from "./components/Hero";
import Features from "./components/Features";
import HowItWorks from "./components/HowItWorks";
import Pricing from "./components/Pricing";
import FAQ from "./components/FAQ";
import Footer from "./components/Footer";

export default function Index({ plans = [] }) {
    return (
        <>
            <Head title="EkinDesk — Helpdesk MSP profesional" />
            <div className="min-h-screen bg-slate-950 text-slate-100 font-[family-name:var(--font-sans,'Source_Sans_3',system-ui,sans-serif)] antialiased">
                <Navbar />
                <Hero />
                <Features />
                <HowItWorks />
                <Pricing plans={plans} />
                <FAQ />
                <Footer />
            </div>
        </>
    );
}
