import { Head } from "@inertiajs/react";
import Navbar from "./components/Navbar";
import Hero from "./components/Hero";
import Features from "./components/Features";
import HowItWorks from "./components/HowItWorks";
import Pricing from "./components/Pricing";
import FAQ from "./components/FAQ";
import Footer from "./components/Footer";
import { surfaceRoot } from "@/lib/marketingTheme";

export default function Index({ plans = [] }) {
    return (
        <>
            <Head title="EkinDesk — Helpdesk MSP profesional" />
            <div className={surfaceRoot}>
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
